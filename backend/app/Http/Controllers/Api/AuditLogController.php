<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;

class AuditLogController extends BaseCrudController
{
    protected string $model = AuditLog::class;
    protected string $resource = AuditLogResource::class;
    protected array $with = ['user'];
    protected array $searchable = ['action', 'module', 'entity'];
    protected array $filterable = ['action' => 'action', 'module' => 'module', 'user_id' => 'user_id'];
}
