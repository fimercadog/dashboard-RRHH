<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessImportJob;
use App\Models\ImportJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    use ResolvesCompany;

    private const VALID_ENTITIES = ['employees', 'clients', 'products'];

    private const PERMISSION_MAP = [
        'employees' => 'employees.manage',
        'clients'   => 'clients.manage',
        'products'  => 'inventory.manage',
    ];

    public function upload(Request $request, string $entity): JsonResponse
    {
        // 1. Validate entity
        if (! in_array($entity, self::VALID_ENTITIES, true)) {
            abort(404, "Entidad de importación no soportada: {$entity}");
        }

        // 2. Check permission
        Gate::authorize(self::PERMISSION_MAP[$entity]);

        // 3. Validate file
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $companyId = $this->companyId($request);

        // 4. Check 409 + Create (mutex via Cache::lock — funciona en SQLite y todos los drivers)
        $lockKey = "import.{$companyId}.{$entity}";
        $lock    = Cache::lock($lockKey, 10);

        if (! $lock->get()) {
            return response()->json([
                'message' => 'Ya existe una importación en proceso para esta entidad.',
            ], 409);
        }

        try {
            $exists = ImportJob::where('company_id', $companyId)
                ->where('entity', $entity)
                ->where('status', 'processing')
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Ya existe una importación en proceso para esta entidad.',
                ], 409);
            }

            $path = $request->file('file')->store("imports/{$companyId}", 'local');

            try {
                $job = ImportJob::create([
                    'company_id' => $companyId,
                    'user_id'    => $request->user()->id,
                    'entity'     => $entity,
                    'status'     => 'pending',
                    'file_path'  => $path,
                    'file_name'  => $request->file('file')->getClientOriginalName(),
                ]);
            } catch (\Throwable $e) {
                Storage::disk('local')->delete($path);
                throw $e;
            }
        } finally {
            $lock->release();
        }

        // 5. Dispatch (fuera del lock)
        ProcessImportJob::dispatch($job->id);

        return response()->json([
            'import_job_id' => $job->id,
            'status'        => 'pending',
        ], 202);
    }

    public function status(Request $request, ImportJob $importJob): JsonResponse
    {
        abort_if($importJob->company_id !== $this->companyId($request), 404);

        $permission = self::PERMISSION_MAP[$importJob->entity] ?? null;
        abort_if($permission === null, 404);
        Gate::authorize($permission);

        return response()->json($importJob->only([
            'id', 'entity', 'status', 'total_rows', 'processed_rows',
            'created_rows', 'skipped_rows', 'failed_rows', 'errors',
            'started_at', 'completed_at',
        ]), 200);
    }
}
