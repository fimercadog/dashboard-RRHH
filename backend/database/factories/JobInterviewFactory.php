<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobInterviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id'         => Company::factory(),
            'job_application_id' => JobApplication::factory(),
            'scheduled_at'       => $this->faker->dateTimeBetween('now', '+30 days'),
            'type'               => $this->faker->randomElement(['presential', 'virtual', 'phone']),
            'result'             => 'pending',
        ];
    }
}
