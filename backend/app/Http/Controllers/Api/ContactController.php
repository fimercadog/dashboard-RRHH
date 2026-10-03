<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ContactResource;
use App\Models\Contact;

class ContactController extends BaseCrudController
{
    protected string $model = Contact::class;
    protected string $resource = ContactResource::class;
    protected array $searchable = ['name', 'email', 'job_title'];
    protected array $filterable = ['client_id' => 'client_id'];
    protected array $with = ['client'];
}
