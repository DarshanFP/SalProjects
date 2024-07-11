<?php

// database/seeders/UsersTableSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create Coordinator
        $coordinator = User::firstOrCreate(
            ['username' => 'coordinator'],
            [
                'name' => 'Coordinator',
                'email' => 'hello@footprint.org.in',
                'password' => Hash::make('login'),
                'role' => 'coordinator',
                'status' => 'active',
            ]
        );
        $coordinator->assignRole('coordinator');

        // Create Provincial
        $provincial = User::firstOrCreate(
            ['username' => 'provincial'],
            [
                'name' => 'Provincial',
                'email' => 'greetings@footprint.org.in',
                'password' => Hash::make('login'),
                'role' => 'provincial',
                'status' => 'active',
                'parent_id' => $coordinator->id,
            ]
        );
        $provincial->assignRole('provincial');

        // Create Executor
        $executor = User::firstOrCreate(
            ['username' => 'executor'],
            [
                'name' => 'Executor',
                'email' => 'bounce@footprint.org.in',
                'password' => Hash::make('login'),
                'role' => 'executor',
                'status' => 'active',
                'parent_id' => $provincial->id,
            ]
        );
        $executor->assignRole('executor');

        // Create Admin
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin',
                'email' => 'darshan@footprint.org.in',
                'password' => Hash::make('login'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );
        $admin->assignRole('admin');
    }
}
