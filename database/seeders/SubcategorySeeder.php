<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubcategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $subcategories = [
            ['id' => '1', 'name' => 'PANT'],
            ['id' => '2', 'name' => 'JOGGING PANT'],
            ['id' => '3', 'name' => 'WOOL BODY'],
            ['id' => '4', 'name' => 'GENERAL'],
            ['id' => '5', 'name' => 'CHILDREN'],
            ['id' => '6', 'name' => 'JACKET'],
            ['id' => '7', 'name' => 'PREMIUM'],
            ['id' => '8', 'name' => 'HOUSEHOLD'],
            ['id' => '9', 'name' => 'SHIRT'],
            ['id' => '10', 'name' => 'SHORTS'],
            ['id' => '11', 'name' => 'TSHIRT'],
            ['id' => '12', 'name' => 'DRESS'],
            ['id' => '13', 'name' => 'SWEATSHIRT'],
            ['id' => '14', 'name' => 'JEANS'],
            ['id' => '15', 'name' => 'BLOUSE'],
            ['id' => '16', 'name' => 'SHORT'],
            ['id' => '17', 'name' => 'SWEATER'],
            ['id' => '18', 'name' => 'Select...']
        ];

        // Insert subcategories into the database
        DB::table('subcategories')->insert($subcategories);
    }
}
