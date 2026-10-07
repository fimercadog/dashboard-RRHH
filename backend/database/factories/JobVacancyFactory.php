<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobVacancyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id'      => Company::factory(),
            'title'           => $this->faker->jobTitle(),
            'description'     => $this->faker->sentence(),
            'opened_at'       => $this->faker->date(),
            'status'          => 'open',
            'vacancies_count' => 1,
        ];
    }
}
