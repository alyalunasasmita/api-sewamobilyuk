<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\DataCar;
use Carbon\Carbon;

class ReservationFactory extends Factory
{
    

public function definition(): array
{
    $start = Carbon::instance(
        fake()->dateTimeBetween('+1 day', '+1 month')
    );
    $count_days = rand(1, 7);
    $end = $start->copy()->addDays($count_days);

    return [
        'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
        'data_car_id' => DataCar::inRandomOrder()->first()?->id ?? Car::factory(),

        'start_date' => $start,
        'end_date' => $end,
        'count_days' => $count_days,

        'total_price' => fake()->numberBetween(300000, 2000000),

        // 'reservations_status' => fake()->randomElement([
        //     'pending',
        //     'approved',
        //     'completed',
        //     'cancelled'
        // ]),
    ];
}
}