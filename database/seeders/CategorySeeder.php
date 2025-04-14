<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            ['id' => '1', 'name' => 'LADIES'],
            ['id' => '2', 'name' => 'CHILDREN'],
            ['id' => '3', 'name' => 'ADULT'],
            ['id' => '4', 'name' => 'GENERAL'],
            ['id' => '5', 'name' => 'MEN'],
            ['id' => '6', 'name' => 'HOUSEHOLD'],
            ['id' => '7', 'name' => 'PREMIUM'],
            ['id' => '8', 'name' => 'UNISEX'],
            ['id' => '9', 'name' => 'Select...']
        ];

        // Insert categories into the database
        DB::table('categories')->insert($categories);
    }
}