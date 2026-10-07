<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\JobCandidate;
use App\Models\JobVacancy;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id'       => Company::factory(),
            'job_vacancy_id'   => JobVacancy::factory(),
            'job_candidate_id' => JobCandidate::factory(),
            'applied_at'       => $this->faker->date(),
            'status'           => 'new',
        ];
    }
}
