<?php

namespace App\Services\Import\Handlers;

use App\Contracts\ImportableEntity;
use App\Models\Client;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ClientImportHandler implements ImportableEntity
{
    public function modelClass(): string
    {
        return Client::class;
    }

    public function requiredColumns(): array
    {
        return ['first_name', 'last_name'];
    }

    public function optionalColumns(): array
    {
        return ['email', 'identification_type', 'identification_number', 'status'];
    }

    public function duplicateKeys(): array
    {
        return [];
    }

    public function validateRow(array $row, int $rowNumber, int $companyId): array
    {
        $rules = [
            'first_name'            => ['required', 'string', 'max:80'],
            'last_name'             => ['required', 'string', 'max:80'],
            'email'                 => ['nullable', 'email', 'max:150'],
            'identification_type'   => ['nullable', 'string', 'max:10'],
            'identification_number' => ['nullable', 'string', 'max:30'],
            'status'                => ['nullable', 'in:active,inactive'],
        ];

        $validator = Validator::make($row, $rules);

        if ($validator->fails()) {
            $messages = implode('; ', $validator->errors()->all());
            throw new \RuntimeException($messages);
        }

        return $validator->validated();
    }

    public function mapRow(array $validated, int $companyId): array
    {
        $row = $validated;
        $row['company_id']   = $companyId;
        $row['client_uuid']  = (string) Str::uuid();
        $row['status']       = $row['status'] ?? 'active';

        $now = now()->toDateTimeString();
        $row['created_at'] = $now;
        $row['updated_at'] = $now;

        return $row;
    }
}
