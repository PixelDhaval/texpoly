<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // Dashboard
            ['name' => 'dashboard', 'display_name' => 'Dashboard', 'group' => 'dashboard'],
            
            // Master Data
            ['name' => 'users', 'display_name' => 'Users', 'group' => 'master'],
            ['name' => 'labels', 'display_name' => 'Labels', 'group' => 'master'],
            ['name' => 'customers', 'display_name' => 'Customers', 'group' => 'master'],
            ['name' => 'categories', 'display_name' => 'Categories', 'group' => 'master'],
            ['name' => 'subcategories', 'display_name' => 'Subcategories', 'group' => 'master'],
            ['name' => 'products', 'display_name' => 'Products', 'group' => 'master'],
            ['name' => 'employees', 'display_name' => 'Employees', 'group' => 'master'],
            ['name' => 'plants', 'display_name' => 'Plants', 'group' => 'master'],
            
            // Operations
            ['name' => 'packinglists', 'display_name' => 'Packing Lists', 'group' => 'operations'],
            ['name' => 'orders', 'display_name' => 'Orders', 'group' => 'operations'],
            ['name' => 'production', 'display_name' => 'Production', 'group' => 'operations'],
            ['name' => 'bales', 'display_name' => 'Bales', 'group' => 'operations'],
            ['name' => 'cancellations', 'display_name' => 'Cancellations', 'group' => 'operations'],
            ['name' => 'repacking', 'display_name' => 'Repacking', 'group' => 'operations'],
            ['name' => 'plant_transfer', 'display_name' => 'Plant Transfer', 'group' => 'operations'],
            
            // Reports
            ['name' => 'reports', 'display_name' => 'Reports', 'group' => 'reports'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
