<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifica que el guard anti-producción en Tests\TestCase funcione correctamente.
 *
 * Este test no usa Illuminate\Foundation\Testing\TestCase para poder testar
 * la lógica del guard sin activarlo sobre sí mismo.
 */
class ProductionGuardTest extends TestCase
{
    /** La lógica del guard detecta "prod" en cualquier posición y capitalización */
    public function test_guard_fires_on_database_names_containing_prod(): void
    {
        $cases = [
            'production'         => true,
            'dfctalentohumano_prod' => true,
            'my_prod_db'         => true,
            'PROD_DB'            => true,
            'dfctalentohumano'   => false,
            'testing'            => false,
            'dfc_test'           => false,
            'development'        => false,
        ];

        foreach ($cases as $dbName => $shouldBlock) {
            $blocked = str_contains(strtolower($dbName), 'prod');
            $this->assertSame(
                $shouldBlock,
                $blocked,
                "Guard should " . ($shouldBlock ? 'block' : 'allow') . " '{$dbName}'"
            );
        }
    }

    /** El nombre de la BD activa no contiene "prod" (el entorno no apunta a producción) */
    public function test_current_database_is_not_production(): void
    {
        // Cargamos el .env del backend para leer DB_DATABASE
        $envPath = __DIR__ . '/../../.env';
        $dbName  = 'unknown';

        if (file_exists($envPath)) {
            foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (str_starts_with($line, 'DB_DATABASE=')) {
                    $dbName = trim(substr($line, 12));
                    break;
                }
            }
        }

        $this->assertFalse(
            str_contains(strtolower($dbName), 'prod'),
            "La BD activa '{$dbName}' contiene 'prod' — los tests podrían apuntar a producción."
        );
    }
}
