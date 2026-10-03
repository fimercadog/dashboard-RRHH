<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\DealResource;
use App\Models\Deal;

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
    protected array $with = ['client', 'owner:id,name'];
}
