<?php

namespace App\Services\Import;

class ErrorAccumulator
{
    private array $errors = [];

    public function add(int $row, string $type, ?string $field, string $message): void
    {
        $this->errors[] = [
            'row'     => $row,
            'type'    => $type,
            'field'   => $field,
            'message' => $message,
        ];
    }

    public function all(): array
    {
        return $this->errors;
    }

    public function isEmpty(): bool
    {
        return empty($this->errors);
    }
}
