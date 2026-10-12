<?php

namespace Tests\Unit\Import;

use App\Services\Import\ErrorAccumulator;
use Tests\TestCase;

class ErrorAccumulatorTest extends TestCase
{
    public function test_distinguishes_validation_vs_transaction_types(): void
    {
        $acc = new ErrorAccumulator;

        $acc->add(1, 'validation', 'email', 'Email inválido.');
        $acc->add(2, 'transaction', null, 'Error de base de datos.');

        $errors = $acc->all();

        $this->assertCount(2, $errors);

        $types = array_column($errors, 'type');
        $this->assertContains('validation', $types);
        $this->assertContains('transaction', $types);

        $rows = array_column($errors, 'row');
        $this->assertContains(1, $rows);
        $this->assertContains(2, $rows);

        // Validation error has a field; transaction error doesn't
        $validationError = array_filter($errors, fn ($e) => $e['type'] === 'validation');
        $validationError = array_values($validationError)[0];
        $this->assertEquals('email', $validationError['field']);

        $transactionError = array_filter($errors, fn ($e) => $e['type'] === 'transaction');
        $transactionError = array_values($transactionError)[0];
        $this->assertNull($transactionError['field']);
    }
}
