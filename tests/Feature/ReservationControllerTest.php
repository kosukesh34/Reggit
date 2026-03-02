<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Member;
use App\Models\Project;
use App\Models\Reservation;
use App\Models\ShopSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ReservationControllerTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_USER_ID = 'test-user-123';

    private const RESERVATION_ENDPOINT = '/api/reservations';

    private function reservationUrl(string $path = ''): string
    {
        return self::RESERVATION_ENDPOINT.$path.'?project_key=default';
    }

    private function createDefaultProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default', 'description' => '', 'is_active' => true]
        );
    }

    /** UserService が使うセッションキーは mobile_order_user_id_{projectId} */
    private function sessionForUser(Project $project, User $user): array
    {
        return ['mobile_order_user_id_'.$project->id => $user->id];
    }

    public function test_index_予約一覧を返すこと(): void
    {
        $project = $this->createDefaultProject();
        $user = User::factory()->create(['line_user_id' => self::TEST_USER_ID, 'project_id' => $project->id]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        Reservation::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->withSession($this->sessionForUser($project, $user))
            ->withHeaders(['X-Line-User-Id' => self::TEST_USER_ID])
            ->get($this->reservationUrl());

        $response->assertStatus(200)
            ->assertJsonStructure(['reservations' => []]);
    }

    public function test_store_予約が作成されること(): void
    {
        $project = $this->createDefaultProject();
        $user = User::factory()->create(['line_user_id' => self::TEST_USER_ID, 'project_id' => $project->id]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $timeSlots = ShopSetting::getReservationTimeSlots($project->id);
        $timeSlot = $timeSlots[0];
        $reservedAt = now()->addDay()->format('Y-m-d').'T'.$timeSlot;

        $response = $this->withSession($this->sessionForUser($project, $user))
            ->withHeaders(['X-Line-User-Id' => self::TEST_USER_ID])
            ->post($this->reservationUrl(), [
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

    public function test_store_必須項目のバリデーションが行われること(): void
    {
        $project = $this->createDefaultProject();
        $user = User::factory()->create(['line_user_id' => self::TEST_USER_ID, 'project_id' => $project->id]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);

        $response = $this->withSession($this->sessionForUser($project, $user))
            ->withHeaders(['X-Line-User-Id' => self::TEST_USER_ID])
            ->post($this->reservationUrl(), []);

        $response->assertStatus(422);
    }

    public function test_cancel_予約がキャンセルされること(): void
    {
        $project = $this->createDefaultProject();
        $user = User::factory()->create(['line_user_id' => self::TEST_USER_ID, 'project_id' => $project->id]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'status' => ReservationStatus::PENDING,
        ]);

        $response = $this->withSession($this->sessionForUser($project, $user))
            ->withHeaders(['X-Line-User-Id' => self::TEST_USER_ID])
            ->post($this->reservationUrl('/'.$reservation->id.'/cancel'));

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'reservation']);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::CANCELLED,
        ]);
    }

    public function test_cancel_完了済み予約のキャンセルは失敗すること(): void
    {
        $project = $this->createDefaultProject();
        $user = User::factory()->create(['line_user_id' => self::TEST_USER_ID, 'project_id' => $project->id]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'status' => ReservationStatus::COMPLETED,
        ]);

        $response = $this->withSession($this->sessionForUser($project, $user))
            ->withHeaders(['X-Line-User-Id' => self::TEST_USER_ID])
            ->post($this->reservationUrl('/'.$reservation->id.'/cancel'));

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function test_index_会員登録が必須であること(): void
    {
        $project = $this->createDefaultProject();
        $user = User::factory()->create(['line_user_id' => self::TEST_USER_ID, 'project_id' => $project->id]);

        $response = $this->withSession($this->sessionForUser($project, $user))
            ->withHeaders(['X-Line-User-Id' => self::TEST_USER_ID])
            ->get($this->reservationUrl());

        $response->assertStatus(403)
            ->assertJson(['error' => '会員登録が必要です']);
    }

    public function test_store_会員登録が必須であること(): void
    {
        $project = $this->createDefaultProject();
        $user = User::factory()->create(['line_user_id' => self::TEST_USER_ID, 'project_id' => $project->id]);
        $timeSlots = ShopSetting::getReservationTimeSlots($project->id);
        $timeSlot = $timeSlots[0];
        $reservedAt = now()->addDay()->format('Y-m-d').'T'.$timeSlot;

        $response = $this->withSession($this->sessionForUser($project, $user))
            ->withHeaders(['X-Line-User-Id' => self::TEST_USER_ID])
            ->post($this->reservationUrl(), [
                'reserved_at' => $reservedAt,
                'number_of_people' => 2,
            ]);

        $response->assertStatus(403)
            ->assertJson(['error' => '会員登録が必要です']);
    }

    public function test_store_休業日の予約は拒否されること(): void
    {
        $project = $this->createDefaultProject();
        $user = User::factory()->create(['line_user_id' => self::TEST_USER_ID, 'project_id' => $project->id]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $closedDate = now()->addDay()->format('Y-m-d');
        $timeSlots = ShopSetting::getReservationTimeSlots($project->id);
        $timeSlot = $timeSlots[0];
        ShopSetting::setValue('closed_dates', [$closedDate], 'json', $project->id);

        $response = $this->withSession($this->sessionForUser($project, $user))
            ->withHeaders(['X-Line-User-Id' => self::TEST_USER_ID])
            ->post($this->reservationUrl(), [
                'reserved_at' => $closedDate.'T'.$timeSlot,
                'number_of_people' => 2,
            ]);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson(['error' => '選択した日は予約できません']);
    }

    public function test_store_満席の枠は拒否されること(): void
    {
        $project = $this->createDefaultProject();
        $user = User::factory()->create(['line_user_id' => self::TEST_USER_ID, 'project_id' => $project->id]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $timeSlots = ShopSetting::getReservationTimeSlots($project->id);
        $timeSlot = $timeSlots[0];
        $reservedAt = Carbon::parse(now()->addDay()->format('Y-m-d').' '.$timeSlot);

        ShopSetting::setValue('reservation_capacity_per_slot', 1, 'integer', $project->id);

        Reservation::factory()->create([
            'reserved_at' => $reservedAt,
            'status' => ReservationStatus::PENDING,
        ]);

        $response = $this->withSession($this->sessionForUser($project, $user))
            ->withHeaders(['X-Line-User-Id' => self::TEST_USER_ID])
            ->post($this->reservationUrl(), [
                'reserved_at' => $reservedAt->format('Y-m-d\TH:i'),
                'number_of_people' => 2,
            ]);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson(['error' => '選択した時間帯は満席です']);
    }
}
