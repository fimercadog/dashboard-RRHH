<?php

namespace Database\Factories;

use App\Models\VacationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VacationRequest>
 */
class VacationRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('now', '+30 days');
        $end   = (clone $start)->modify('+5 days');
        return [
            'start_date'     => $start->format('Y-m-d'),
            'end_date'       => $end->format('Y-m-d'),
            'requested_days' => 5,
            'status'         => 'pending',
        ];
    }
}
