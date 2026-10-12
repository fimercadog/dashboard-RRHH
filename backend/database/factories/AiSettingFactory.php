<?php

namespace Database\Factories;

use App\Models\AiSetting;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiSettingFactory extends Factory
{
    protected $model = AiSetting::class;

    public function definition(): array
    {
        return [
            'company_id'     => Company::factory(),
            'provider'       => 'openai',
            'model'          => null,
            'api_key'        => null,
            'base_url'       => null,
            'is_active'      => false,
            'last_tested_at' => null,
        ];
    }
}
