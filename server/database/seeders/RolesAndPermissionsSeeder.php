<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions,  to avoid conflicts or stale data.
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // the resources that need permision for crud operation
        $entities = ['products', 'orders', 'users'];
        $actions = ['create', 'read', 'update', 'delete'];

        // Create all permissions
        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                Permission::create(['name' => "{$action} {$entity}"]);
            }
        }

        // Create Roles
        $adminRole = Role::create(['name' => 'Admin']);
        $sellerRole = Role::create(['name' => 'Seller']);

        // Admin gets all permissions
        $adminRole->givePermissionTo(Permission::all());

        // Seller gets specific permissions
        $sellerRole->givePermissionTo([
            'read products',
            'create orders',
            'read orders',
            'update orders'
            // no deleting orders or managing users for seller
        ]);

        // (Optional) Create a test Admin user
        $adminUser = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
        ]);
        $adminUser->assignRole('Admin');
    }
}
