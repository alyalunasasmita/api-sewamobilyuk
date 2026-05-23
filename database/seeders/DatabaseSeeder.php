<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\DataCar;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'username' => 'admin', 
            'role' => 'admin'
        ]);

        User::factory()->create([
            'name' => 'Test customer',
            'email' => 'customer@example.com',
            'password' => Hash::make('customer123'),
            'username' => 'customer', 
        ]);

        // DataCar::factory(20)->create();
    }
}
