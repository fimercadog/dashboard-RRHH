<?php

namespace Database\Factories;

use App\Models\SickLeave;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SickLeave>
 */
class SickLeaveFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-30 days', 'now');
        $end   = (clone $start)->modify('+3 days');
        return [
            'start_date'  => $start->format('Y-m-d'),
            'end_date'    => $end->format('Y-m-d'),
            'days'        => 3,
            'type'        => 'illness',
            'description' => $this->faker->sentence(),
            'status'      => 'pending',
        ];
    }
}
