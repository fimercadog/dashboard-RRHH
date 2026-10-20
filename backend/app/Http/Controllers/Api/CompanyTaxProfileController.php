<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CompanyTaxProfileResource;
use App\Models\CompanyTaxProfile;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyTaxProfileController extends BaseCrudController
{
    protected string $model    = CompanyTaxProfile::class;
    protected string $resource = CompanyTaxProfileResource::class;
    protected array  $filterable = ['review_status' => 'review_status'];
    protected array  $immutableOnUpdate = ['company_id', 'created_by', 'review_status', 'verified_by', 'verified_at'];

    /**
     * Valida el perfil tributario. Solo via este endpoint se puede transicionar review_status.
     * Requiere permiso payroll.validate_tax_profile.
     */
    public function validate(Request $request, string $id, AuditService $audit): JsonResponse
    {
        $companyId = $this->companyId($request);

        $profile = CompanyTaxProfile::where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $data = $request->validate([
            'verified_by' => 'nullable|string|max:200',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $profile->forceFill([
            'review_status' => 'validated',
            'verified_by'   => $data['verified_by'] ?? null,
            'verified_at'   => now(),
        ])->save();

        $audit->record('tax_profile_validated', $profile, $request);

        return response()->json(new CompanyTaxProfileResource($profile->fresh()));
    }
}
