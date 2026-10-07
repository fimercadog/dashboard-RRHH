<?php

namespace Database\Factories;

use App\Models\EmployeeDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeDocument>
 */
class EmployeeDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_type' => 'contract',
            'name'          => $this->faker->words(3, true),
            'file_path'     => 'documents/test-' . $this->faker->uuid() . '.pdf',
            'status'        => 'active',
        ];
    }
}
