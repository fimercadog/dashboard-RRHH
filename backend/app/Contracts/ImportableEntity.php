<?php

namespace App\Contracts;

interface ImportableEntity
{
    public function requiredColumns(): array;
    public function optionalColumns(): array;
    public function duplicateKeys(): array;
    public function validateRow(array $row, int $rowNumber, int $companyId): array;
    public function mapRow(array $validated, int $companyId): array;
    public function modelClass(): string;
}
