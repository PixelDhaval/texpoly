<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $modules = [
            'users', 'labels', 'customers', 'categories', 'subcategories',
            'products', 'employees', 'plants', 'packinglists', 'orders',
            'production', 'bales', 'cancellations', 'repacking', 'plant_transfer', 
        ];

        $permissions = [];

        // Generate CRUD permissions for each module
        foreach ($modules as $module) {
            $permissions = array_merge($permissions, [
                [
                    'name' => "$module.view",
                    'display_name' => ucfirst($module) . ' View',
                    'group' => $this->getGroup($module)
                ],
                [
                    'name' => "$module.create",
                    'display_name' => ucfirst($module) . ' Create',
                    'group' => $this->getGroup($module)
                ],
                [
                    'name' => "$module.edit",
                    'display_name' => ucfirst($module) . ' Edit',
                    'group' => $this->getGroup($module)
                ],
                [
                    'name' => "$module.delete",
                    'display_name' => ucfirst($module) . ' Delete',
                    'group' => $this->getGroup($module)
                ],
            ]);
        }

        // Add special permissions
        $permissions = array_merge($permissions, [
            ['name' => 'dashboard', 'display_name' => 'Dashboard', 'group' => 'dashboard'],
            ['name' => 'reports.view', 'display_name' => 'View Reports', 'group' => 'reports'],
            ['name' => 'products.history.view', 'display_name' => 'View Product History', 'group' => 'records'],
        ]);

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }

    private function getGroup($module)
    {
        $masterData = ['users', 'labels', 'customers', 'categories', 'subcategories', 'products', 'employees', 'plants'];
        $operations = ['packinglists', 'orders', 'production', 'plant_transfer', 'repacking'];
        $records = ['bales', 'cancellations'];

        if (in_array($module, $masterData)) return 'master';
        if (in_array($module, $operations)) return 'operations';
        if (in_array($module, $records)) return 'records';
        return 'other';
    }
}
