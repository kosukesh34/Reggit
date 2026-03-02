<?php

namespace Database\Factories;

use App\Enums\QueueStatus;
use App\Models\Member;
use App\Models\QueueEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

class QueueEntryFactory extends Factory
{
    protected $model = QueueEntry::class;

    public function definition(): array
    {
        return [
            'project_id' => 1,
            'member_id' => Member::factory(),
            'guest_name' => $this->faker->name(),
            'guest_phone' => $this->faker->phoneNumber(),
            'party_size' => $this->faker->numberBetween(1, 6),
            'status' => QueueStatus::WAITING,
            'queue_number' => $this->faker->numberBetween(1, 50),
            'called_at' => null,
            'entered_at' => null,
        ];
    }
}
