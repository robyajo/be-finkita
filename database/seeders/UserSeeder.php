<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin User 1
        User::create([
            'name' => 'Super Admin',
            'email' => 'su@finkita.web.id',
            'password' => Hash::make('Password123'),
            'role' => 'SU',
            'provider' => 'CREDENTIALS',
            'avatar' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=su',
            'email_verified_at' => now(),
            'has_password' => true,
        ]);

        // Admin User 2
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@finkita.web.id',
            'password' => Hash::make('Password123'),
            'role' => 'ADMIN',
            'provider' => 'CREDENTIALS',
            'avatar' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=admin',
            'email_verified_at' => now(),
            'has_password' => true,
        ]);

        // Regular User
        User::create([
            'name' => 'Regular User',
            'email' => 'user@finkita.web.id',
            'password' => Hash::make('Password123'),
            'role' => 'USER',
            'provider' => 'CREDENTIALS',
            'avatar' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=user',
            'email_verified_at' => now(),
            'has_password' => true,
        ]);
    }
}
