<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'company_id'      => Company::factory(),
            'created_by'      => User::factory(),
            'name'            => $this->faker->sentence(3),
            'type'            => $this->faker->randomElement(Campaign::TYPES),
            'status'          => 'draft',
            'audience_source' => $this->faker->randomElement(['erp_employees', 'erp_clients', 'erp_leads']),
            'audience_filters' => null,
            'message_subject' => $this->faker->sentence(),
            'message_body'    => $this->faker->paragraph(),
        ];
    }
}
