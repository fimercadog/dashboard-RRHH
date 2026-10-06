<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'company_id'             => Company::factory(),
            'first_name'             => $this->faker->firstName(),
            'last_name'              => $this->faker->lastName(),
            'email'                  => $this->faker->unique()->safeEmail(),
            'phone'                  => $this->faker->phoneNumber(),
            'status'                 => 'active',
            'identification_type'    => 'cedula',
            'identification_number'  => $this->faker->numerify('##########'),
        ];
    }
}
