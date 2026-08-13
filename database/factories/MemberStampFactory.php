<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberStamp;
use App\Models\StampCard;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberStampFactory extends Factory
{
    protected $model = MemberStamp::class;

    public function definition(): array
    {
        return [
            'project_id' => 1,
            'member_id' => Member::factory(),
            'stamp_card_id' => StampCard::factory(),
            'current_stamps' => 0,
            'cycle_count' => 0,
            'completed_at' => null,
        ];
    }
}
