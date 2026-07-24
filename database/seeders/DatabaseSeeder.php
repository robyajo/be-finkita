<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
        ]);

        \Illuminate\Support\Facades\Artisan::call('passport:client', [
            '--personal' => true,
            '--name' => 'Finkita Personal Access Client',
            '--no-interaction' => true,
        ]);
    }
}
