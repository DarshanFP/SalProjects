<?php

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
        // Create Coordinator India
        $coordinatorIndia = User::firstOrCreate(
            ['username' => 'coordinatorIndia'],
            [
                'name' => 'Nirmala Mathew',
                'email' => 'nirmalamathewsas@gmail.com',
                'password' => Hash::make('login'),
                'role' => 'coordinator',
                'status' => 'active',
                'province' => 'Generalate',
                'center' => 'Generalate',
            ]
        );
        $coordinatorIndia->assignRole('coordinator');

        // Create Coordinator Luzern
        $coordinatorLuzern = User::firstOrCreate(
            ['username' => 'coordinatorLuzern'],
            [
                'name' => 'Samuel Imbach',  // Replace with actual name
                'email' => 'S.Imbach@mission-stanna.ch',  // Replace with actual email
                'password' => Hash::make('login'),
                'role' => 'coordinator',
                'status' => 'active',
                'province' => 'Luzern',
                'center' => 'Luzern',
            ]
        );
        $coordinatorLuzern->assignRole('coordinator');

        // Create Provincial Bangalore
        $provincialBangalore = User::firstOrCreate(
            ['username' => 'provincialBangalore'],
            [
                'name' => 'Provincial Bangalore',
                'email' => 'provincial.bangalore@example.com',
                'password' => Hash::make('login'),
                'role' => 'provincial',
                'status' => 'active',
                'province' => 'Bangalore',
                'parent_id' => $coordinatorIndia->id,
            ]
        );
        $provincialBangalore->assignRole('provincial');

        // Create Provincial Vijayawada
        $provincialVijayawada = User::firstOrCreate(
            ['username' => 'provincialVijayawada'],
            [
                'name' => 'Provincial Vijayawada',
                'email' => 'provincial.vijayawada@example.com',
                'password' => Hash::make('login'),
                'role' => 'provincial',
                'status' => 'active',
                'province' => 'Vijayawada',
                'parent_id' => $coordinatorIndia->id,
            ]
        );
        $provincialVijayawada->assignRole('provincial');

        // Create Provincial Visakhapatnam
        $provincialVisakhapatnam = User::firstOrCreate(
            ['username' => 'provincialVisakhapatnam'],
            [
                'name' => 'Provincial Visakhapatnam',
                'email' => 'provincial.visakhapatnam@example.com',
                'password' => Hash::make('login'),
                'role' => 'provincial',
                'status' => 'active',
                'province' => 'Visakhapatnam',
                'parent_id' => $coordinatorIndia->id,
            ]
        );
        $provincialVisakhapatnam->assignRole('provincial');

        // Create Executors for Bangalore
        $executorBangalore1 = User::firstOrCreate(
            ['username' => 'executorBangalore1'],
            [
                'name' => 'Executor Bangalore 1',
                'email' => 'executor.bangalore1@example.com',
                'password' => Hash::make('login'),
                'role' => 'executor',
                'status' => 'active',
                'province' => 'Bangalore',
                'center' => 'Center 1',
                'parent_id' => $provincialBangalore->id,
            ]
        );
        $executorBangalore1->assignRole('executor');

        $executorBangalore2 = User::firstOrCreate(
            ['username' => 'executorBangalore2'],
            [
                'name' => 'Executor Bangalore 2',
                'email' => 'executor.bangalore2@example.com',
                'password' => Hash::make('login'),
                'role' => 'executor',
                'status' => 'active',
                'province' => 'Bangalore',
                'center' => 'Center 2',
                'parent_id' => $provincialBangalore->id,
            ]
        );
        $executorBangalore2->assignRole('executor');

        // Create Executors for Vijayawada
        $executorVijayawada1 = User::firstOrCreate(
            ['username' => 'executorVijayawada1'],
            [
                'name' => 'Executor Vijayawada 1',
                'email' => 'executor.vijayawada1@example.com',
                'password' => Hash::make('login'),
                'role' => 'executor',
                'status' => 'active',
                'province' => 'Vijayawada',
                'center' => 'Center 1',
                'parent_id' => $provincialVijayawada->id,
            ]
        );
        $executorVijayawada1->assignRole('executor');

        $executorVijayawada2 = User::firstOrCreate(
            ['username' => 'executorVijayawada2'],
            [
                'name' => 'Executor Vijayawada 2',
                'email' => 'executor.vijayawada2@example.com',
                'password' => Hash::make('login'),
                'role' => 'executor',
                'status' => 'active',
                'province' => 'Vijayawada',
                'center' => 'Center 2',
                'parent_id' => $provincialVijayawada->id,
            ]
        );
        $executorVijayawada2->assignRole('executor');

        // Create Executors for Visakhapatnam
        $executorVisakhapatnam1 = User::firstOrCreate(
            ['username' => 'executorVisakhapatnam1'],
            [
                'name' => 'Executor Visakhapatnam 1',
                'email' => 'executor.visakhapatnam1@example.com',
                'password' => Hash::make('login'),
                'role' => 'executor',
                'status' => 'active',
                'province' => 'Visakhapatnam',
                'center' => 'Center 1',
                'parent_id' => $provincialVisakhapatnam->id,
            ]
        );
        $executorVisakhapatnam1->assignRole('executor');

        $executorVisakhapatnam2 = User::firstOrCreate(
            ['username' => 'executorVisakhapatnam2'],
            [
                'name' => 'Executor Visakhapatnam 2',
                'email' => 'executor.visakhapatnam2@example.com',
                'password' => Hash::make('login'),
                'role' => 'executor',
                'status' => 'active',
                'province' => 'Visakhapatnam',
                'center' => 'Center 2',
                'parent_id' => $provincialVisakhapatnam->id,
            ]
        );
        $executorVisakhapatnam2->assignRole('executor');

        // Create General user
        $general = User::firstOrCreate(
            ['username' => 'generalUser'],
            [
                'name' => 'Sr. Elizabeth Antony',
                'email' => 'general@example.com',
                'password' => Hash::make('login'),
                'role' => 'general',
                'status' => 'active',
                'province' => 'none',
            ]
        );
        $general->assignRole('general');

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
