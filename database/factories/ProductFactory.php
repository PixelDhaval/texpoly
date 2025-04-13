<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->sentence,
            'short_code' => strtoupper($this->faker->unique()->lexify('????')),
            'category_id' => Category::factory(),
            'subcategory_id' => Subcategory::factory(),
            'label_name' => $this->faker->words(2, true),
            'grade' => $this->faker->randomElement(['A', 'B', 'C', 'W']),
            'unit' => $this->faker->randomElement(['KGS', 'LBS', 'PCS']),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'quantity' => $this->faker->numberBetween(100, 1000),
            'weight' => $this->faker->numberBetween(1, 100),
        ];
    }
}
