<?php

namespace Database\Factories;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reserved_at' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'number_of_people' => $this->faker->numberBetween(1, 10),
            'status' => ReservationStatus::PENDING,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
