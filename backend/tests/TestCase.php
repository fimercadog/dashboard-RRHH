<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('app.env') === 'production') {
            $this->markTestSkipped('Bloqueado: APP_ENV=production');
        }

        $conn   = config('database.default');
        $dbName = strtolower((string) config("database.connections.{$conn}.database"));
        $allowed = [':memory:', 'dfc_test', 'dfc_testing', 'testing'];
        $isAllowed = in_array($dbName, $allowed) || str_ends_with($dbName, '_test') || str_ends_with($dbName, '_testing');
        if (! $isAllowed) {
            $this->markTestSkipped("Bloqueado: BD no reconocida como entorno de test ({$dbName})");
        }
    }
}
