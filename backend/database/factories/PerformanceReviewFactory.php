<?php

namespace Database\Factories;

use App\Models\PerformanceReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PerformanceReview> */
class PerformanceReviewFactory extends Factory
{
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-6 months', '-1 month');
        return [
            'period_start'   => $start->format('Y-m-d'),
            'period_end'     => $this->faker->dateTimeBetween($start, 'now')->format('Y-m-d'),
            'status'         => 'draft',
        ];
    }
}
