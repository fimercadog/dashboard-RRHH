<?php

namespace Database\Factories;

use App\Models\PermissionRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PermissionRequest>
 */
class PermissionRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('now', '+14 days');
        $end   = (clone $start)->modify('+1 day');
        return [
            'type'           => 'personal',
            'start_date'     => $start->format('Y-m-d'),
            'end_date'       => $end->format('Y-m-d'),
            'requested_days' => 1,
            'status'         => 'pending',
        ];
    }
}
