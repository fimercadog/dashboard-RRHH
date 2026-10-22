<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends BaseCrudController
{
    protected string $model = Contact::class;
    protected string $resource = ContactResource::class;
    protected array $searchable = ['name', 'email', 'job_title'];
    protected array $filterable = ['client_id' => 'client_id'];
    protected array $with = ['client'];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        return response()->json([
            'total' => Contact::where('company_id', $cid)->count(),
        ]);
    }
}
