<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create sample users for each role
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => 'password',
                'email_verified_at' => now(),
                'role' => Role::ADMINISTRATOR,
            ]
        );

        User::firstOrCreate(
            ['email' => 'technician@example.com'],
            [
                'name' => 'Technician User',
                'password' => 'password',
                'email_verified_at' => now(),
                'role' => Role::TECHNICIAN,
            ]
        );

        User::firstOrCreate(
            ['email' => 'counter@example.com'],
            [
                'name' => 'Counter Staff',
                'password' => 'password',
                'email_verified_at' => now(),
                'role' => Role::COUNTER_STAFF,
            ]
        );

        // Keep the original test user as counter staff (default role)
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
                'role' => Role::COUNTER_STAFF,
            ]
        );
    }
}
