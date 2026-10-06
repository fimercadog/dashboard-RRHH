<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SegmentResource;
use App\Models\Segment;
use App\Services\AuditService;
use Illuminate\Http\Request;

class SegmentController extends BaseCrudController
{
    protected string $model = Segment::class;
    protected string $resource = SegmentResource::class;
    protected array $searchable = ['name', 'description'];

    public function syncClients(Request $request, string $id, AuditService $audit)
    {
        $segment = Segment::query()->where('company_id', $this->companyId($request))->findOrFail($id);
        $clientIds = $request->validate(['client_ids' => ['required', 'array'], 'client_ids.*' => ['integer']])['client_ids'];
        $segment->clients()->sync($clientIds);
        $audit->record('updated', $segment, $request);

        return response()->json(['synced' => count($clientIds)]);
    }
}
