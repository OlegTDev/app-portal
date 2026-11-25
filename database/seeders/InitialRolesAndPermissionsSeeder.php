<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InitialRolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions
        $permissions = [
            // News permissions
            'view-news',
            'create-news',
            'update-news',
            'delete-news',
            'manage-access-news',

            // Department permissions
            'view-departments',
            'create-departments',
            'update-departments',
            'delete-departments',
            'manage-access-departments',

            // Automation permissions
            'view-automation',
            'create-automation',
            'update-automation',
            'delete-automation',
            'manage-access-automation',

            // Test permissions
            'view-tests',
            'create-tests',
            'update-tests',
            'delete-tests',
            'manage-access-tests',

            // Menu permissions
            'view-menus',
            'create-menus',
            'update-menus',
            'delete-menus',

            // Declare campaign permissions
            'view-declare-campaign',
            'update-declare-campaign',
            'manage-access-declare-campaign',

            // Admin permissions
            'manage-roles',
            'manage-access-groups',
            'sync-ad',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create admin role with all permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Create moderator role with limited permissions
        $moderatorRole = Role::firstOrCreate(['name' => 'moderator']);
        $moderatorRole->givePermissionTo([
            'view-news',
            'create-news',
            'update-news',
            'view-departments',
            'view-automation',
            'view-tests',
            'view-declare-campaign',
            'update-declare-campaign',
        ]);

        $this->command->info('Initial roles and permissions seeded successfully.');
    }
}
