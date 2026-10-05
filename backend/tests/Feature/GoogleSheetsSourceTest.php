<?php

namespace Tests\Feature;

use App\Communications\Sources\GoogleSheetsSource;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * GoogleSheetsSource unit + integration tests.
 * All HTTP calls are intercepted by Http::fake() — no real API key needed.
 */
class GoogleSheetsSourceTest extends TestCase
{
    use RefreshDatabase;

    private const SPREADSHEET_ID = '1cRFZ-T4HmhnZsaTJ6Db-gkuez3PXai5vGGovweHme6s';
    private const API_URL        = 'https://sheets.googleapis.com/v4/spreadsheets/' . self::SPREADSHEET_ID . '/values/Sheet1';

    private Company $company;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['communications.manage', 'communications.view', 'dashboard.view'] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $role = Role::firstOrCreate(['name' => 'Administrador de empresa', 'guard_name' => 'web']);
        $role->syncPermissions(['communications.manage', 'communications.view', 'dashboard.view']);

        $this->company = Company::factory()->create(['name' => 'Test Sheets SA']);
        $this->admin   = User::factory()->create(['company_id' => $this->company->id]);
        $this->admin->assignRole('Administrador de empresa');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function makeSource(): GoogleSheetsSource
    {
        return new GoogleSheetsSource();
    }

    private function configureApiKey(): void
    {
        Config::set('services.google.sheets_api_key', 'test-api-key');
        Config::set('services.google.sheets_spreadsheet_id', self::SPREADSHEET_ID);
    }

    /** Typical sheet with all recognized columns. */
    private function fullSheetResponse(): array
    {
        return [
            'range'  => 'Sheet1!A1:H10',
            'values' => [
                ['nombre', 'telefono', 'email', 'tipo', 'segmento', 'estado'],
                ['Juan Pérez',   '3001234567', 'juan@test.com',    'cliente',    'promociones', 'activo'],
                ['Ana Gómez',    '3109876543', 'ana@test.com',     'empleado',   'interno',     'activo'],
                ['Pedro Ruiz',   '3201111111', 'pedro@test.com',   'cliente',    'promociones', 'inactivo'],
                ['María Torres', '3002222222', 'maria@test.com',   'proveedor',  'general',     'activo'],
                ['',             '',           '',                 '',           '',            ''],
            ],
        ];
    }

    // ── isReady / notReadyMessage ────────────────────────────────────────────

    public function test_is_not_ready_without_api_key(): void
    {
        Config::set('services.google.sheets_api_key', null);
        $this->assertFalse($this->makeSource()->isReady());
    }

    public function test_is_ready_with_api_key(): void
    {
        $this->configureApiKey();
        $this->assertTrue($this->makeSource()->isReady());
    }

    public function test_not_ready_message_is_informative(): void
    {
        $msg = $this->makeSource()->notReadyMessage();
        $this->assertStringContainsString('GOOGLE_SHEETS_API_KEY', $msg);
    }

    // ── Column detection ────────────────────────────────────────────────────

    public function test_detects_spanish_column_names(): void
    {
        $this->configureApiKey();
        Http::fake([self::API_URL . '*' => Http::response($this->fullSheetResponse())]);

        $result = $this->makeSource()->preview(1);

        // All 6 columns should be detected.
        $this->assertContains('name',    $result['columns_detected']);
        $this->assertContains('phone',   $result['columns_detected']);
        $this->assertContains('email',   $result['columns_detected']);
        $this->assertContains('type',    $result['columns_detected']);
        $this->assertContains('segment', $result['columns_detected']);
        $this->assertContains('status',  $result['columns_detected']);
    }

    public function test_detects_english_column_names(): void
    {
        $this->configureApiKey();
        Http::fake([self::API_URL . '*' => Http::response([
            'values' => [
                ['name', 'phone', 'email', 'type', 'segment', 'status'],
                ['Alice', '5551234', 'alice@test.com', 'client', 'promo', 'active'],
            ],
        ])]);

        $result = $this->makeSource()->preview(1);

        $this->assertContains('name',  $result['columns_detected']);
        $this->assertContains('email', $result['columns_detected']);
    }

    public function test_handles_missing_optional_columns(): void
    {
        $this->configureApiKey();
        // Only name + email — no phone, type, segment, status.
        Http::fake([self::API_URL . '*' => Http::response([
            'values' => [
                ['nombre', 'email'],
                ['Carlos', 'carlos@test.com'],
            ],
        ])]);

        $result = $this->makeSource()->preview(1);

        $this->assertEquals(1, $result['count']);
        $this->assertContains('name',  $result['columns_detected']);
        $this->assertContains('email', $result['columns_detected']);
        $this->assertNotContains('phone',   $result['columns_detected']);
        $this->assertNotContains('segment', $result['columns_detected']);
        $this->assertEquals('carlos@test.com', $result['sample'][0]['email']);
    }

    // ── Normalization ────────────────────────────────────────────────────────

    public function test_normalizes_full_row_correctly(): void
    {
        $this->configureApiKey();
        Http::fake([self::API_URL . '*' => Http::response($this->fullSheetResponse())]);

        $result = $this->makeSource()->preview(1);

        // 5 data rows — 1 blank = 4 valid recipients.
        $this->assertEquals(4, $result['count']);
    }

    public function test_skips_blank_name_rows(): void
    {
        $this->configureApiKey();
        Http::fake([self::API_URL . '*' => Http::response([
            'values' => [
                ['nombre', 'email'],
                ['',       'orphan@test.com'],   // no name — must be skipped
                ['Valid',  'valid@test.com'],
            ],
        ])]);

        $result = $this->makeSource()->preview(1);
        $this->assertEquals(1, $result['count']);
    }

    public function test_sample_includes_name_and_email(): void
    {
        $this->configureApiKey();
        Http::fake([self::API_URL . '*' => Http::response($this->fullSheetResponse())]);

        $result = $this->makeSource()->preview(1);

        foreach ($result['sample'] as $entry) {
            $this->assertArrayHasKey('name',  $entry);
            $this->assertArrayHasKey('email', $entry);
        }
    }

    public function test_sample_capped_at_five(): void
    {
        $this->configureApiKey();
        $rows = [['nombre', 'email']];
        for ($i = 1; $i <= 10; $i++) {
            $rows[] = ["Persona {$i}", "p{$i}@test.com"];
        }
        Http::fake([self::API_URL . '*' => Http::response(['values' => $rows])]);

        $result = $this->makeSource()->preview(1);

        $this->assertEquals(10, $result['count']);
        $this->assertLessThanOrEqual(5, count($result['sample']));
    }

    // ── Filtering ────────────────────────────────────────────────────────────

    public function test_filters_by_segment(): void
    {
        $this->configureApiKey();
        Http::fake([self::API_URL . '*' => Http::response($this->fullSheetResponse())]);

        $result = $this->makeSource()->preview(1, ['segment' => 'promociones']);

        // 2 rows have segmento=promociones (Juan, Pedro).
        $this->assertEquals(2, $result['count']);
    }

    public function test_filters_by_status(): void
    {
        $this->configureApiKey();
        Http::fake([self::API_URL . '*' => Http::response($this->fullSheetResponse())]);

        $result = $this->makeSource()->preview(1, ['status' => 'activo']);

        // Juan, Ana, María = 3 activos. Pedro is inactivo.
        $this->assertEquals(3, $result['count']);
    }

    public function test_filters_by_segment_and_status_combined(): void
    {
        $this->configureApiKey();
        Http::fake([self::API_URL . '*' => Http::response($this->fullSheetResponse())]);

        $result = $this->makeSource()->preview(1, ['segment' => 'promociones', 'status' => 'activo']);

        // Only Juan matches both.
        $this->assertEquals(1, $result['count']);
    }

    public function test_filter_by_nonexistent_segment_returns_zero(): void
    {
        $this->configureApiKey();
        Http::fake([self::API_URL . '*' => Http::response($this->fullSheetResponse())]);

        $result = $this->makeSource()->preview(1, ['segment' => 'no_existe']);
        $this->assertEquals(0, $result['count']);
    }

    // ── Empty / edge cases ───────────────────────────────────────────────────

    public function test_empty_sheet_returns_zero_count(): void
    {
        $this->configureApiKey();
        Http::fake([self::API_URL . '*' => Http::response(['values' => []])]);

        $result = $this->makeSource()->preview(1);

        $this->assertEquals(0, $result['count']);
        $this->assertEmpty($result['sample']);
        $this->assertEmpty($result['columns_detected']);
    }

    public function test_sheet_with_only_headers_returns_zero_count(): void
    {
        $this->configureApiKey();
        Http::fake([self::API_URL . '*' => Http::response([
            'values' => [['nombre', 'email', 'telefono']],
        ])]);

        $result = $this->makeSource()->preview(1);
        $this->assertEquals(0, $result['count']);
    }

    // ── Error handling ───────────────────────────────────────────────────────

    public function test_throws_on_auth_error_401(): void
    {
        $this->configureApiKey();
        Http::fake([self::API_URL . '*' => Http::response(['error' => ['message' => 'API key not valid']], 401)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/autenticación/i');

        $this->makeSource()->preview(1);
    }

    public function test_throws_on_forbidden_403(): void
    {
        $this->configureApiKey();
        Http::fake([self::API_URL . '*' => Http::response(['error' => ['message' => 'The caller does not have permission']], 403)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/autenticación/i');

        $this->makeSource()->preview(1);
    }

    public function test_throws_on_sheet_not_found_404(): void
    {
        $this->configureApiKey();
        Config::set('services.google.sheets_spreadsheet_id', self::SPREADSHEET_ID);
        Http::fake([self::API_URL . '*' => Http::response([
            'error' => ['message' => 'Unable to parse range: Sheet1'],
        ], 404)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Sheet1/');

        $this->makeSource()->preview(1);
    }

    public function test_throws_when_no_spreadsheet_id_configured(): void
    {
        $this->configureApiKey();
        Config::set('services.google.sheets_spreadsheet_id', null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/spreadsheet/i');

        // No spreadsheet_id in filters either.
        $this->makeSource()->preview(1, []);
    }

    // ── Via HTTP endpoint ────────────────────────────────────────────────────

    public function test_preview_audience_endpoint_uses_google_sheets_source(): void
    {
        $this->configureApiKey();
        Http::fake(['https://sheets.googleapis.com/*' => Http::response($this->fullSheetResponse())]);

        $campaign = Campaign::factory()->create([
            'company_id'      => $this->company->id,
            'created_by'      => $this->admin->id,
            'audience_source' => 'google_sheets',
            'audience_filters' => ['spreadsheet_id' => self::SPREADSHEET_ID],
        ]);

        $res = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/campaigns/{$campaign->id}/preview-audience");

        $res->assertOk()
            ->assertJsonPath('ready', true)
            ->assertJsonStructure(['ready', 'count', 'sample', 'columns_detected']);

        $this->assertEquals(4, $res->json('count'));
    }

    public function test_preview_endpoint_returns_not_ready_when_no_api_key(): void
    {
        Config::set('services.google.sheets_api_key', null);

        $campaign = Campaign::factory()->create([
            'company_id'      => $this->company->id,
            'created_by'      => $this->admin->id,
            'audience_source' => 'google_sheets',
        ]);

        $res = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/campaigns/{$campaign->id}/preview-audience");

        $res->assertOk()->assertJsonPath('ready', false);
        $this->assertNotEmpty($res->json('message'));
    }

    public function test_sources_endpoint_shows_google_sheets_as_not_ready_without_key(): void
    {
        Config::set('services.google.sheets_api_key', null);

        $res = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/campaigns/sources');

        $res->assertOk();
        $sheets = collect($res->json())->firstWhere('key', 'google_sheets');

        $this->assertFalse($sheets['ready']);
        $this->assertStringContainsString('GOOGLE_SHEETS_API_KEY', $sheets['message']);
    }

    public function test_sources_endpoint_shows_google_sheets_as_ready_with_key(): void
    {
        $this->configureApiKey();

        $res = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/campaigns/sources');

        $res->assertOk();
        $sheets = collect($res->json())->firstWhere('key', 'google_sheets');

        $this->assertTrue($sheets['ready']);
    }

    // ── Multitenancy / permissions ───────────────────────────────────────────

    public function test_cannot_preview_other_company_campaign(): void
    {
        $other        = Company::factory()->create(['name' => 'Otra SA']);
        $otherAdmin   = User::factory()->create(['company_id' => $other->id]);
        $otherAdmin->assignRole('Administrador de empresa');

        $campaign = Campaign::factory()->create([
            'company_id'      => $other->id,
            'created_by'      => $otherAdmin->id,
            'audience_source' => 'google_sheets',
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/campaigns/{$campaign->id}/preview-audience")
            ->assertStatus(403);
    }
}
