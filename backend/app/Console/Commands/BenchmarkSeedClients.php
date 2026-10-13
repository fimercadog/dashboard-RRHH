<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BenchmarkSeedClients extends Command
{
    protected $signature = 'benchmark:seed-clients {--clean : Eliminar la empresa de benchmark y sus clientes}';
    protected $description = 'Genera 100,000 clientes de prueba para benchmarking de rendimiento';

    private const COMPANY_NAME = 'BENCHMARK_TEST_COMPANY_100K';
    private const TOTAL = 100_000;
    private const BATCH  = 500;

    private array $firstNames = ['Carlos','María','Juan','Ana','Luis','Sofía','Pedro','Laura','Miguel','Valentina','Diego','Camila','Andrés','Isabella','Felipe','Natalia','José','Daniela','Alejandro','Paola'];
    private array $lastNames  = ['García','Martínez','López','Rodríguez','González','Hernández','Pérez','Torres','Ramírez','Sánchez','Castro','Vargas','Morales','Jiménez','Romero','Díaz','Suárez','Mendoza','Reyes','Guerrero'];
    private array $identificationTypes = ['CC','NIT','CE','TI','PP'];
    private array $cities = ['Bogotá','Medellín','Cali','Barranquilla','Cartagena','Cúcuta','Bucaramanga','Pereira','Manizales','Santa Marta','Ibagué','Villavicencio','Pasto','Montería','Armenia'];

    public function handle(): int
    {
        if ($this->option('clean')) {
            return $this->clean();
        }

        return $this->seed();
    }

    private function clean(): int
    {
        $company = Company::where('name', self::COMPANY_NAME)->first();

        if (! $company) {
            $this->warn('No se encontró la empresa "' . self::COMPANY_NAME . '". Nada que limpiar.');
            return self::SUCCESS;
        }

        $company->delete();
        $this->info('Empresa y clientes eliminados (cascade).');

        return self::SUCCESS;
    }

    private function seed(): int
    {
        if (Company::where('name', self::COMPANY_NAME)->exists()) {
            $this->error('La empresa "' . self::COMPANY_NAME . '" ya existe. Use --clean primero.');
            return self::FAILURE;
        }

        $company = Company::create(['name' => self::COMPANY_NAME]);
        $companyId = $company->id;

        $now = now()->toDateTimeString();
        $fnCount = count($this->firstNames);
        $lnCount = count($this->lastNames);
        $idtCount = count($this->identificationTypes);
        $cityCount = count($this->cities);

        $start   = microtime(true);
        $chunk   = [];
        $inserted = 0;

        for ($i = 1; $i <= self::TOTAL; $i++) {
            $firstName = $this->firstNames[rand(0, $fnCount - 1)];
            $lastName  = $this->lastNames[rand(0, $lnCount - 1)] . ' ' . $this->lastNames[rand(0, $lnCount - 1)];

            $chunk[] = [
                'company_id'            => $companyId,
                'first_name'            => $firstName,
                'last_name'             => $lastName,
                'identification_type'   => $this->identificationTypes[rand(0, $idtCount - 1)],
                'identification_number' => (string) rand(10_000_000, 9_999_999_999),
                'email'                 => rand(0, 4) === 0
                    ? null
                    : strtolower($firstName) . '.' . explode(' ', strtolower($lastName))[0] . rand(100, 999) . '@ejemplo.co',
                'phone'                 => rand(0, 4) === 0
                    ? null
                    : '3' . str_pad((string) rand(0, 99), 2, '0', STR_PAD_LEFT) . rand(1_000_000, 9_999_999),
                'city'                  => rand(0, 9) === 0 ? null : $this->cities[rand(0, $cityCount - 1)],
                'status'                => rand(0, 9) === 0 ? 'inactive' : 'active',
                'company_name'          => null,
                'address'               => null,
                'notes'                 => null,
                'created_at'            => $now,
                'updated_at'            => $now,
            ];

            if (count($chunk) === self::BATCH) {
                DB::table('clients')->insert($chunk);
                $inserted += self::BATCH;
                $chunk = [];

                if ($inserted % 10_000 === 0) {
                    $this->line("Generados {$inserted}/" . self::TOTAL . "...");
                }
            }
        }

        if ($chunk) {
            DB::table('clients')->insert($chunk);
            $inserted += count($chunk);
        }

        $elapsed = round(microtime(true) - $start, 2);
        $this->info("Completado. company_id={$companyId}, insertados={$inserted}, tiempo={$elapsed}s");

        return self::SUCCESS;
    }
}
