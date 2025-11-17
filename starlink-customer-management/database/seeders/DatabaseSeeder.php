<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@starlink.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
        ]);

        // Create sample Admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin2@starlink.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // Create sample Viewer
        User::create([
            'name' => 'Viewer',
            'email' => 'viewer@starlink.com',
            'password' => Hash::make('password123'),
            'role' => 'viewer',
        ]);
    }
}
