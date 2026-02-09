<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create roles
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'coordinator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'provincial', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'executor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'general', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'applicant', 'guard_name' => 'web']);

        // You can create permissions and assign them to roles as needed
        // Permission::create(['name' => 'edit articles']);
        // $role = Role::findByName('admin');
        // $role->givePermissionTo('edit articles');
    }
}
