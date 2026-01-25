<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Member;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationControllerTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_USER_ID = 'test-user-123';
    private const RESERVATION_ENDPOINT = '/api/reservations';


    public function testIndexReturnsReservations(): void
    {
        $user = User::factory()->create(['line_user_id' => self::TEST_USER_ID]);
        $member = Member::factory()->create(['user_id' => $user->id]);
        Reservation::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->withSession(['mobile_order_user_id' => $user->id])
            ->withHeaders(['X-Line-User-Id' => self::TEST_USER_ID])
            ->get(self::RESERVATION_ENDPOINT);

        $response->assertStatus(200)
            ->assertJsonStructure(['reservations' => []]);
    }

    public function testStoreCreatesReservation(): void
    {
        $user = User::factory()->create(['line_user_id' => self::TEST_USER_ID]);
        $member = Member::factory()->create(['user_id' => $user->id]);
        $reservedAt = now()->addDay()->format('Y-m-d\TH:i');

        $response = $this->withSession(['mobile_order_user_id' => $user->id])
            ->withHeaders(['X-Line-User-Id' => self::TEST_USER_ID])
            ->post(self::RESERVATION_ENDPOINT, [
                'reserved_at' => $reservedAt,
                'number_of_people' => 2,
                'notes' => 'Test reservation',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'reservation']);

        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->id,
            'number_of_people' => 2,
            'status' => ReservationStatus::PENDING,
        ]);
    }

    public function testStoreValidatesRequiredFields(): void
    {
        $user = User::factory()->create(['line_user_id' => self::TEST_USER_ID]);
        $member = Member::factory()->create(['user_id' => $user->id]);

        $response = $this->withSession(['mobile_order_user_id' => $user->id])
            ->withHeaders(['X-Line-User-Id' => self::TEST_USER_ID])
            ->post(self::RESERVATION_ENDPOINT, []);

        $response->assertStatus(422);
    }

    public function testCancelCancelsReservation(): void
    {
        $user = User::factory()->create(['line_user_id' => self::TEST_USER_ID]);
        $member = Member::factory()->create(['user_id' => $user->id]);
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'status' => ReservationStatus::PENDING,
        ]);

        $response = $this->withSession(['mobile_order_user_id' => $user->id])
            ->withHeaders(['X-Line-User-Id' => self::TEST_USER_ID])
            ->post(self::RESERVATION_ENDPOINT . '/' . $reservation->id . '/cancel');

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'reservation']);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::CANCELLED,
        ]);
    }

    public function testCancelFailsForCompletedReservation(): void
    {
        $user = User::factory()->create(['line_user_id' => self::TEST_USER_ID]);
        $member = Member::factory()->create(['user_id' => $user->id]);
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'status' => ReservationStatus::COMPLETED,
        ]);

        $response = $this->withSession(['mobile_order_user_id' => $user->id])
            ->withHeaders(['X-Line-User-Id' => self::TEST_USER_ID])
            ->post(self::RESERVATION_ENDPOINT . '/' . $reservation->id . '/cancel');

        $response->assertStatus(400);
    }

    public function testIndexRequiresMembership(): void
    {
        $user = User::factory()->create(['line_user_id' => self::TEST_USER_ID]);

        $response = $this->withSession(['mobile_order_user_id' => $user->id])
            ->withHeaders(['X-Line-User-Id' => self::TEST_USER_ID])
            ->get(self::RESERVATION_ENDPOINT);

        $response->assertStatus(403)
            ->assertJson(['error' => '会員登録が必要です']);
    }

    public function testStoreRequiresMembership(): void
    {
        $user = User::factory()->create(['line_user_id' => self::TEST_USER_ID]);
        $reservedAt = now()->addDay()->format('Y-m-d\TH:i');

        $response = $this->withSession(['mobile_order_user_id' => $user->id])
            ->withHeaders(['X-Line-User-Id' => self::TEST_USER_ID])
            ->post(self::RESERVATION_ENDPOINT, [
                'reserved_at' => $reservedAt,
                'number_of_people' => 2,
            ]);

        $response->assertStatus(403)
            ->assertJson(['error' => '会員登録が必要です']);
    }
}
