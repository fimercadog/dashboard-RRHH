<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobCandidateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id'            => Company::factory(),
            'first_name'            => $this->faker->firstName(),
            'last_name'             => $this->faker->lastName(),
            'identification_number' => $this->faker->unique()->numerify('##########'),
            'email'                 => $this->faker->unique()->safeEmail(),
            'phone'                 => $this->faker->phoneNumber(),
        ];
    }
}
