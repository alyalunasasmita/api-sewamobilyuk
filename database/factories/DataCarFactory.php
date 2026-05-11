<?php

namespace Database\Factories;

use App\Models\DataCar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataCar>
 */
class DataCarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_car' => fake()->randomElement([
                'Toyota Avanza',
                'Honda Brio',
                'Mitsubishi Pajero',
                'Toyota Fortuner',
                'Honda Jazz'
            ]),

            'passenger_capacity' => fake()->numberBetween(2, 8),

            'model' => fake()->word(),

            'year_of_car' => fake()->numberBetween(2018, 2025),

            'price' => fake()->numberBetween(200000, 1500000),

            'description' => fake()->sentence(),

            'plate_number' => strtoupper(fake()->bothify('B #### ??')),

            'kategori' => fake()->randomElement([
                'MPV',
                'SUV',
                'sedan',
                'hatchback'
            ]),

            'transmisi' => fake()->randomElement([
                'automatic',
                'manual'
            ]),

            'image' => 'Datacar/default.jpg'
        ];
    }
}
