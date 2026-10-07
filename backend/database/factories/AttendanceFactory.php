<?php

namespace Database\Factories;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date'     => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'check_in' => '08:00:00',
            'status'   => 'present',
        ];
    }
}
