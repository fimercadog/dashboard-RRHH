<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\DealResource;
use App\Models\Deal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealController extends BaseCrudController
{
    protected string $model = Deal::class;
    protected string $resource = DealResource::class;
    protected array $searchable = ['title', 'notes'];
    protected array $filterable = [
        'stage'     => 'stage',
        'client_id' => 'client_id',
        'owner_id'  => 'owner_id',
    ];
    protected array $with = ['client:id,company_name,first_name,last_name', 'owner:id,name'];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q = Deal::where('company_id', $cid);
        return response()->json([
            'total' => $q->count(),
            'open'  => $q->clone()->whereNotIn('stage', ['won', 'lost'])->count(),
            'won'   => $q->clone()->where('stage', 'won')->count(),
            'lost'  => $q->clone()->where('stage', 'lost')->count(),
        ]);
    }
}
