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
                Permission::firstOrCreate(['name' => "{$action} {$entity}"]);
            }
        }

        // Rename existing  old capitalised names
        $this->renameRoleIfExists('Admin', 'admin');
        $this->renameRoleIfExists('Seller', 'seller');

        // Create Roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $sellerRole = Role::firstOrCreate(['name' => 'seller', 'guard_name' => 'api']);


        // Admin gets all permissions
        $adminRole->syncPermissions(Permission::all());

        // Seller gets specific permissions
        $sellerRole->syncPermissions([
            'read products',
            'create orders',
            'read orders',
            'update orders',
            'create products',
            'update products'
            //sellers can now add new pppproducts and delete products
        ]);

        //test user who is admin
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@app.com'], //as a search creteria
            [
                'name' => 'Super Admin',
                'password' => 'secretAdmin123', // will be hashed automatically
            ]
        );

        $adminUser->syncRoles('admin');
    }

    // rename Roles with capital letters to lower case
    private function renameRoleIfExists(string $oldName, string $newName): void
    {
        // Check for exact old name (case‑sensitive)
        $oldExists = Role::whereRaw('BINARY `name` = ?', [$oldName])->exists();
        // Check for exact new name (case‑sensitive)
        $newExists = Role::whereRaw('BINARY `name` = ?', [$newName])->exists();

        if ($oldExists && !$newExists) {
            Role::whereRaw('BINARY `name` = ?', [$oldName])->update(['name' => $newName]);
        }
    }
}


