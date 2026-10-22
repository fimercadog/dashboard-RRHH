<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SstIncidentResource;
use App\Models\SstIncident;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SstIncidentController extends BaseCrudController
{
    protected string $model    = SstIncident::class;
    protected string $resource = SstIncidentResource::class;
    protected array  $with     = ['employee'];
    protected array  $searchable  = ['description', 'location'];
    protected array  $filterable  = [
        'type'     => 'type',
        'severity' => 'severity',
        'status'   => 'status',
    ];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q   = SstIncident::where('company_id', $cid);
        return response()->json([
            'total'       => $q->clone()->count(),
            'open'        => $q->clone()->where('status', '!=', 'closed')->count(),
            'closed'      => $q->clone()->where('status', 'closed')->count(),
            'this_month'  => $q->clone()->whereMonth('incident_date', now()->month)->whereYear('incident_date', now()->year)->count(),
        ]);
    }

    public function close(Request $request, string $id, AuditService $audit): JsonResponse
    {
        $cid      = $this->companyId($request);
        $incident = SstIncident::where('company_id', $cid)->findOrFail($id);
        $old      = $incident->getOriginal();

        $incident->update(['status' => 'closed', 'resolved_at' => now()]);
        $audit->record('closed', $incident, $request, $old);
        Cache::increment("company_version_{$cid}");

        return (new SstIncidentResource($incident->load('employee')))->response();
    }
}
