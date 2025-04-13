<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Label;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Employee;
use App\Models\Product;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create independent models first
        Label::factory(10)->create();
        Category::factory(10)->create();
        Subcategory::factory(10)->create();
        Employee::factory(15)->create();

        // Create customers with existing labels
        Customer::factory(10)->create([
            'label_id' => fn() => Label::inRandomOrder()->first()->id
        ]);

        // Create products with existing categories and subcategories
        Product::factory(400)->create([
            'category_id' => fn() => Category::inRandomOrder()->first()->id,
            'subcategory_id' => fn() => Subcategory::inRandomOrder()->first()->id
        ]);
    }
}
