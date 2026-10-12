<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\WhatsappSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class WhatsappSettingFactory extends Factory
{
    protected $model = WhatsappSetting::class;

    public function definition(): array
    {
        return [
            'company_id'           => Company::factory(),
            'phone_number_id'      => null,
            'waba_id'              => null,
            'access_token'         => null,
            'webhook_verify_token' => null,
            'is_active'            => false,
            'webhook_verified_at'  => null,
        ];
    }
}
