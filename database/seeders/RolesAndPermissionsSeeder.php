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
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'coordinator']);
        Role::create(['name' => 'provincial']);
        Role::create(['name' => 'executor']);
        Role::create(['name' => 'general']);  // Adding the general role

        // You can create permissions and assign them to roles as needed
        // Permission::create(['name' => 'edit articles']);
        // $role = Role::findByName('admin');
        // $role->givePermissionTo('edit articles');
    }
}
