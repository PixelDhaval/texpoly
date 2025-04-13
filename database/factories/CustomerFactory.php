<?php

namespace Database\Factories;

use App\Models\Label;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
            'phone' => $this->faker->phoneNumber,
            'country' => $this->faker->country,
            'label_id' => Label::factory(),
            'short_code' => strtoupper($this->faker->unique()->lexify('????')),
            'is_active' => true,
            'is_qr' => true,
            'is_bale_no' => true,
            'is_printed_by' => true,
        ];
    }
}
