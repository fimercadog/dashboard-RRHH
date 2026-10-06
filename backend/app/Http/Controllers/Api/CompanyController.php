<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\AuditService;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    use ResolvesCompany;

    public function show(Request $request)
    {
        return response()->json(['data' => $this->company($request)]);
    }

    public function update(Request $request, AuditService $audit)
    {
        $company = $this->company($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nit' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'locale' => ['nullable', 'string', 'max:10'],
            'date_format' => ['nullable', 'string', 'max:20'],
        ]);

        $old = $company->getOriginal();
        $company->update($data);
        $audit->record('updated', $company, $request, $old);

        return response()->json(['data' => $company->fresh()]);
    }

    private function company(Request $request): Company
    {
        return Company::findOrFail($this->companyId($request));
    }
}
