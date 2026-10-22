<?php

namespace Database\Factories;

use App\Models\SstIncident;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SstIncident> */
class SstIncidentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'incident_date' => $this->faker->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'type'          => $this->faker->randomElement(['near_miss', 'incident', 'accident', 'occupational_disease']),
            'severity'      => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'location'      => $this->faker->optional()->words(3, true),
            'description'   => $this->faker->sentence(),
            'status'        => 'reported',
        ];
    }
}
