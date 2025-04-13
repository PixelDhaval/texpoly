<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Load JSON data
        $jsonFilePath = base_path('texpoly-2.product.json');
        $jsonData = json_decode(file_get_contents($jsonFilePath), true);

        // Map categories and subcategories to their IDs
        $categories = [
            'LADIES' => 1,
            'CHILDREN' => 2,
            'ADULT' => 3,
            'GENERAL' => 4,
            'MEN' => 5,
            'HOUSEHOLD' => 6,
            'PREMIUM' => 7,
            'UNISEX' => 8,
            'Select...' => 9
        ];

        $subcategories = [
            'PANT' => 1,
            'JOGGING PANT' => 2,
            'WOOL BODY' => 3,
            'GENERAL' => 4,
            'CHILDREN' => 5,
            'JACKET' => 6,
            'PREMIUM' => 7,
            'HOUSEHOLD' => 8,
            'SHIRT' => 9,
            'SHORTS' => 10,
            'TSHIRT' => 11,
            'DRESS' => 12,
            'SWEATSHIRT' => 13,
            'JEANS' => 14,
            'BLOUSE' => 15,
            'SHORT' => 16,
            'SWEATER' => 17,
            'Select...' => 18
        ];

        // Prepare data for insertion
        $products = [];
        foreach ($jsonData as $item) {
            // Skip invalid entries
            if (!isset($item['itemCode'], $item['itemName'], $item['category'], $item['section'])) {
                continue;
            }

            $products[] = [
                'short_code' => $item['itemCode'],
                'name' => $item['itemName'],
                'description' => $item['labelName'] ?? null,
                'category_id' => $categories[$item['category']] ?? null,
                'subcategory_id' => $subcategories[$item['section']] ?? null,
                'label_name' => $item['labelName'] ?? null,
                'grade' => $item['grade'] ?? null,
                'unit' => $item['packingMeasure'] ?? null,
                'price' => isset($item['masterPrice']) ? (float) $item['masterPrice'] : 0,
                'quantity' => isset($item['packing']) ? (int) $item['packing'] : 0,
                'weight' => isset($item['baleweight']) ? (int) $item['baleweight'] : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert data into the database
        DB::table('products')->insert($products);
    }
}