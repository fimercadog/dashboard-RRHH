<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $seq = 0;
        $seq++;
        return [
            'employee_code'           => 'EMP-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'first_name'              => $this->faker->firstName(),
            'last_name'               => $this->faker->lastName(),
            'identification_type'     => 'CC',
            'identification_number'   => $this->faker->unique()->numerify('##########'),
            'hire_date'               => $this->faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
            'employment_status'       => 'active',
        ];
    }
}
