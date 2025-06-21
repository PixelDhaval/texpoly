<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class GateServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function register()
    {
        parent::register();
    }

    public function boot()
    {
        $this->registerPolicies();

        $permissions = [
            'users', 'labels', 'customers', 'categories', 'subcategories',
            'products', 'employees', 'plants', 'packinglists', 'orders',
            'production', 'bales', 'cancellations', 'repacking', 'plant_transfer',
            'dashboard', 'reports', 'product_history', 'section_wise_labour', 'daily-production-report',
            'customer-stock-report', 'total-stock-report', 'grade-wise-report', 'product-wise-daily-report',
        ];

        foreach ($permissions as $permission) {
            Gate::define($permission, function ($user) use ($permission) {
                return $user->hasPermission($permission);
            });
        }

        Gate::before(function ($user, $ability) {
            // Optional: Add super admin check if needed
            // if ($user->isSuperAdmin()) return true;
        });
    }
}
