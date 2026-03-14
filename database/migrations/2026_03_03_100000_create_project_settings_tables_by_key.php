<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 設定をキー（論理グループ）ごとのテーブルに分割する。
 * 既存の project_settings または shop_settings からデータを移行する。
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createBusinessSettingsTable();
        $this->createLineLiffSettingsTable();
        $this->createFeatureFlagsTable();
        $this->createAdminUiSettingsTable();
        $this->createProfileSmaregiSettingsTable();
        $this->createMemberPointsSettingsTable();
        $this->createMemberRankSettingsTable();
        $this->createWalletSettingsTable();
        $this->createAnnouncementSettingsTable();

        $this->migrateData();

        if (Schema::hasTable('project_settings')) {
            Schema::drop('project_settings');
        }
    }

    private function createBusinessSettingsTable(): void
    {
        Schema::create('project_business_settings', function (Blueprint $table) {
            $table->foreignId('project_id')->primary()->constrained('projects')->onDelete('cascade');
            $table->string('business_hours_start', 10)->default('10:00');
            $table->string('business_hours_end', 10)->default('22:00');
            $table->json('reservation_time_slots')->nullable();
            $table->json('closed_days')->nullable();
            $table->json('closed_dates')->nullable();
            $table->unsignedInteger('advance_booking_days')->default(30);
            $table->unsignedInteger('reservation_capacity_per_slot')->default(20);
            $table->unsignedInteger('queue_avg_serve_minutes')->default(10);
            $table->timestamps();
        });
    }

    private function createLineLiffSettingsTable(): void
    {
        Schema::create('project_line_liff_settings', function (Blueprint $table) {
            $table->foreignId('project_id')->primary()->constrained('projects')->onDelete('cascade');
            $table->string('line_primary_color', 20)->default('#000000');
            $table->string('line_primary_dark', 20)->default('#333333');
            $table->string('line_success_color', 20)->default('#000000');
            $table->string('line_danger_color', 20)->default('#dc3545');
            $table->string('line_liff_id')->nullable();
            $table->string('line_liff_url')->nullable();
            $table->json('liff_builder_blocks')->nullable();
            $table->json('liff_tab_layouts')->nullable();
            $table->json('liff_tab_settings')->nullable();
            $table->json('liff_saved_screens')->nullable();
            $table->text('liff_custom_css')->nullable();
            $table->string('order_mode', 20)->default('both');
            $table->json('frontend_menu_items')->nullable();
            $table->timestamps();
        });
    }

    private function createFeatureFlagsTable(): void
    {
        Schema::create('project_feature_flags', function (Blueprint $table) {
            $table->foreignId('project_id')->primary()->constrained('projects')->onDelete('cascade');
            $table->boolean('feature_home_enabled')->default(true);
            $table->boolean('feature_orders_enabled')->default(true);
            $table->boolean('feature_reservations_enabled')->default(true);
            $table->boolean('feature_queue_enabled')->default(true);
            $table->boolean('feature_stamps_enabled')->default(true);
            $table->boolean('feature_coupons_enabled')->default(true);
            $table->boolean('feature_members_enabled')->default(true);
            $table->boolean('feature_announcements_enabled')->default(true);
            $table->boolean('feature_payments_enabled')->default(true);
            $table->boolean('feature_gallery_enabled')->default(true);
            $table->timestamps();
        });
    }

    private function createAdminUiSettingsTable(): void
    {
        Schema::create('project_admin_ui_settings', function (Blueprint $table) {
            $table->foreignId('project_id')->primary()->constrained('projects')->onDelete('cascade');
            $table->string('admin_sidebar_color')->nullable();
            $table->string('admin_header_color')->nullable();
            $table->string('admin_primary_color')->nullable();
            $table->string('stamp_default_image_url')->nullable();
            $table->string('coupon_default_image_url')->nullable();
            $table->string('frontend_logo_url')->nullable();
            $table->string('frontend_header_text')->nullable();
            $table->timestamps();
        });
    }

    private function createProfileSmaregiSettingsTable(): void
    {
        Schema::create('project_profile_smaregi_settings', function (Blueprint $table) {
            $table->foreignId('project_id')->primary()->constrained('projects')->onDelete('cascade');
            $table->json('profile_field_settings')->nullable();
            $table->string('smaregi_contract_id')->nullable();
            $table->timestamps();
        });
    }

    private function createMemberPointsSettingsTable(): void
    {
        Schema::create('project_member_points_settings', function (Blueprint $table) {
            $table->foreignId('project_id')->primary()->constrained('projects')->onDelete('cascade');
            $table->unsignedInteger('point_earn_rate')->default(100);
            $table->unsignedInteger('points_per_order')->default(0);
            $table->unsignedInteger('stamp_earn_per_amount')->default(0);
            $table->unsignedBigInteger('order_stamp_card_id')->nullable();
            $table->unsignedInteger('points_valid_days')->default(365);
            $table->timestamps();
        });
    }

    private function createMemberRankSettingsTable(): void
    {
        Schema::create('project_member_rank_settings', function (Blueprint $table) {
            $table->foreignId('project_id')->primary()->constrained('projects')->onDelete('cascade');
            $table->string('member_rank_basis', 20)->default('points');
            $table->string('member_code_type', 20)->default('random');
            $table->string('member_code_format', 20)->default('numeric');
            $table->unsignedTinyInteger('member_code_length')->default(10);
            $table->string('member_code_prefix')->default('');
            $table->unsignedInteger('member_code_next_number')->default(1);
            $table->boolean('show_points_display')->default(true);
            $table->boolean('show_rank_progress')->default(true);
            $table->boolean('show_member_card_for_guest')->default(true);
            $table->string('member_card_design', 30)->default('default');
            $table->string('member_card_display_type', 20)->default('card');
            $table->string('member_card_code_type', 20)->default('barcode');
            $table->json('member_rank_settings')->nullable();
            $table->timestamps();
        });
    }

    private function createWalletSettingsTable(): void
    {
        Schema::create('project_wallet_settings', function (Blueprint $table) {
            $table->foreignId('project_id')->primary()->constrained('projects')->onDelete('cascade');
            $table->string('wallet_google_program_logo_url')->nullable();
            $table->string('wallet_google_hero_image_url')->nullable();
            $table->timestamps();
        });
    }

    private function createAnnouncementSettingsTable(): void
    {
        Schema::create('project_announcement_settings', function (Blueprint $table) {
            $table->foreignId('project_id')->primary()->constrained('projects')->onDelete('cascade');
            $table->boolean('announcement_default_pinned')->default(false);
            $table->unsignedInteger('announcement_default_expiry_days')->default(0);
            $table->timestamps();
        });
    }

    private function migrateData(): void
    {
        $source = Schema::hasTable('project_settings')
            ? DB::table('project_settings')->get()->map(fn ($r) => (object) (array) $r)
            : $this->gatherFromShopSettings();

        $now = now();
        foreach ($source as $row) {
            $projectId = $row->project_id ?? null;
            if ($projectId === null) {
                continue;
            }
            $projectId = (int) $projectId;

            $this->insertBusinessSettings($projectId, $row, $now);
            $this->insertLineLiffSettings($projectId, $row, $now);
            $this->insertFeatureFlags($projectId, $row, $now);
            $this->insertAdminUiSettings($projectId, $row, $now);
            $this->insertProfileSmaregiSettings($projectId, $row, $now);
            $this->insertMemberPointsSettings($projectId, $row, $now);
            $this->insertMemberRankSettings($projectId, $row, $now);
            $this->insertWalletSettings($projectId, $row, $now);
            $this->insertAnnouncementSettings($projectId, $row, $now);
        }

        $this->ensureAllProjectsHaveRows();
    }

    private function gatherFromShopSettings(): \Illuminate\Support\Collection
    {
        if (! Schema::hasTable('shop_settings')) {
            return collect();
        }
        $rows = DB::table('shop_settings')->get()->groupBy('project_id');
        $collection = collect();
        foreach ($rows as $projectId => $settings) {
            $obj = (object) array_merge(['project_id' => (int) $projectId], (array) $this->defaultRow());
            foreach ($settings as $s) {
                $key = $s->key;
                $value = $s->value;
                $type = $s->type ?? 'string';
                if ($type === 'integer') {
                    $value = (int) $value;
                } elseif ($type === 'boolean') {
                    $value = in_array(strtolower((string) $value), ['1', 'true', 'yes'], true);
                }
                $obj->{$key} = $value;
            }
            $collection->push($obj);
        }
        return $collection;
    }

    private function defaultRow(): array
    {
        return [
            'business_hours_start' => '10:00',
            'business_hours_end' => '22:00',
            'reservation_time_slots' => json_encode(['10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00']),
            'closed_days' => json_encode([]),
            'closed_dates' => json_encode([]),
            'advance_booking_days' => 30,
            'reservation_capacity_per_slot' => 20,
            'queue_avg_serve_minutes' => 10,
            'line_primary_color' => '#000000',
            'line_primary_dark' => '#333333',
            'line_success_color' => '#000000',
            'line_danger_color' => '#dc3545',
            'order_mode' => 'both',
            'feature_home_enabled' => true,
            'feature_orders_enabled' => true,
            'feature_reservations_enabled' => true,
            'feature_queue_enabled' => true,
            'feature_stamps_enabled' => true,
            'feature_coupons_enabled' => true,
            'feature_members_enabled' => true,
            'feature_announcements_enabled' => true,
            'feature_payments_enabled' => true,
            'feature_gallery_enabled' => true,
            'point_earn_rate' => 100,
            'points_per_order' => 0,
            'stamp_earn_per_amount' => 0,
            'points_valid_days' => 365,
            'member_rank_basis' => 'points',
            'member_code_type' => 'random',
            'member_code_format' => 'numeric',
            'member_code_length' => 10,
            'member_code_prefix' => '',
            'member_code_next_number' => 1,
            'show_points_display' => true,
            'show_rank_progress' => true,
            'show_member_card_for_guest' => true,
            'member_card_design' => 'default',
            'member_card_display_type' => 'card',
            'member_card_code_type' => 'barcode',
            'announcement_default_pinned' => false,
            'announcement_default_expiry_days' => 0,
        ];
    }

    private function insertBusinessSettings(int $projectId, $row, $now): void
    {
        DB::table('project_business_settings')->insert([
            'project_id' => $projectId,
            'business_hours_start' => $row->business_hours_start ?? '10:00',
            'business_hours_end' => $row->business_hours_end ?? '22:00',
            'reservation_time_slots' => $row->reservation_time_slots ?? json_encode(['10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00']),
            'closed_days' => $row->closed_days ?? json_encode([]),
            'closed_dates' => $row->closed_dates ?? json_encode([]),
            'advance_booking_days' => (int) ($row->advance_booking_days ?? 30),
            'reservation_capacity_per_slot' => (int) ($row->reservation_capacity_per_slot ?? 20),
            'queue_avg_serve_minutes' => (int) ($row->queue_avg_serve_minutes ?? 10),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertLineLiffSettings(int $projectId, $row, $now): void
    {
        DB::table('project_line_liff_settings')->insert([
            'project_id' => $projectId,
            'line_primary_color' => $row->line_primary_color ?? '#000000',
            'line_primary_dark' => $row->line_primary_dark ?? '#333333',
            'line_success_color' => $row->line_success_color ?? '#000000',
            'line_danger_color' => $row->line_danger_color ?? '#dc3545',
            'line_liff_id' => $row->line_liff_id ?? null,
            'line_liff_url' => $row->line_liff_url ?? null,
            'liff_builder_blocks' => $row->liff_builder_blocks ?? null,
            'liff_tab_layouts' => $row->liff_tab_layouts ?? null,
            'liff_tab_settings' => $row->liff_tab_settings ?? null,
            'liff_saved_screens' => $row->liff_saved_screens ?? null,
            'liff_custom_css' => $row->liff_custom_css ?? null,
            'order_mode' => $row->order_mode ?? 'both',
            'frontend_menu_items' => $row->frontend_menu_items ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertFeatureFlags(int $projectId, $row, $now): void
    {
        $bool = fn ($v) => filter_var($v ?? true, FILTER_VALIDATE_BOOLEAN);
        DB::table('project_feature_flags')->insert([
            'project_id' => $projectId,
            'feature_home_enabled' => $bool($row->feature_home_enabled ?? true),
            'feature_orders_enabled' => $bool($row->feature_orders_enabled ?? true),
            'feature_reservations_enabled' => $bool($row->feature_reservations_enabled ?? true),
            'feature_queue_enabled' => $bool($row->feature_queue_enabled ?? true),
            'feature_stamps_enabled' => $bool($row->feature_stamps_enabled ?? true),
            'feature_coupons_enabled' => $bool($row->feature_coupons_enabled ?? true),
            'feature_members_enabled' => $bool($row->feature_members_enabled ?? true),
            'feature_announcements_enabled' => $bool($row->feature_announcements_enabled ?? true),
            'feature_payments_enabled' => $bool($row->feature_payments_enabled ?? true),
            'feature_gallery_enabled' => $bool($row->feature_gallery_enabled ?? true),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertAdminUiSettings(int $projectId, $row, $now): void
    {
        DB::table('project_admin_ui_settings')->insert([
            'project_id' => $projectId,
            'admin_sidebar_color' => $row->admin_sidebar_color ?? null,
            'admin_header_color' => $row->admin_header_color ?? null,
            'admin_primary_color' => $row->admin_primary_color ?? null,
            'stamp_default_image_url' => $row->stamp_default_image_url ?? null,
            'coupon_default_image_url' => $row->coupon_default_image_url ?? null,
            'frontend_logo_url' => $row->frontend_logo_url ?? null,
            'frontend_header_text' => $row->frontend_header_text ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertProfileSmaregiSettings(int $projectId, $row, $now): void
    {
        DB::table('project_profile_smaregi_settings')->insert([
            'project_id' => $projectId,
            'profile_field_settings' => $row->profile_field_settings ?? null,
            'smaregi_contract_id' => $row->smaregi_contract_id ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertMemberPointsSettings(int $projectId, $row, $now): void
    {
        DB::table('project_member_points_settings')->insert([
            'project_id' => $projectId,
            'point_earn_rate' => (int) ($row->point_earn_rate ?? 100),
            'points_per_order' => (int) ($row->points_per_order ?? 0),
            'stamp_earn_per_amount' => (int) ($row->stamp_earn_per_amount ?? 0),
            'order_stamp_card_id' => isset($row->order_stamp_card_id) && $row->order_stamp_card_id !== '' ? (int) $row->order_stamp_card_id : null,
            'points_valid_days' => (int) ($row->points_valid_days ?? 365),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertMemberRankSettings(int $projectId, $row, $now): void
    {
        $bool = fn ($v) => filter_var($v ?? true, FILTER_VALIDATE_BOOLEAN);
        DB::table('project_member_rank_settings')->insert([
            'project_id' => $projectId,
            'member_rank_basis' => $row->member_rank_basis ?? 'points',
            'member_code_type' => $row->member_code_type ?? 'random',
            'member_code_format' => $row->member_code_format ?? 'numeric',
            'member_code_length' => (int) ($row->member_code_length ?? 10),
            'member_code_prefix' => $row->member_code_prefix ?? '',
            'member_code_next_number' => (int) ($row->member_code_next_number ?? 1),
            'show_points_display' => $bool($row->show_points_display ?? true),
            'show_rank_progress' => $bool($row->show_rank_progress ?? true),
            'show_member_card_for_guest' => $bool($row->show_member_card_for_guest ?? true),
            'member_card_design' => $row->member_card_design ?? 'default',
            'member_card_display_type' => $row->member_card_display_type ?? 'card',
            'member_card_code_type' => $row->member_card_code_type ?? 'barcode',
            'member_rank_settings' => $row->member_rank_settings ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertWalletSettings(int $projectId, $row, $now): void
    {
        DB::table('project_wallet_settings')->insert([
            'project_id' => $projectId,
            'wallet_google_program_logo_url' => $row->wallet_google_program_logo_url ?? null,
            'wallet_google_hero_image_url' => $row->wallet_google_hero_image_url ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertAnnouncementSettings(int $projectId, $row, $now): void
    {
        DB::table('project_announcement_settings')->insert([
            'project_id' => $projectId,
            'announcement_default_pinned' => filter_var($row->announcement_default_pinned ?? false, FILTER_VALIDATE_BOOLEAN),
            'announcement_default_expiry_days' => (int) ($row->announcement_default_expiry_days ?? 0),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureAllProjectsHaveRows(): void
    {
        $projectIds = DB::table('projects')->pluck('id');
        $now = now();
        $defaults = (object) array_merge(['project_id' => 0], $this->defaultRow());
        foreach ($projectIds as $pid) {
            $pid = (int) $pid;
            $tables = [
                'project_business_settings',
                'project_line_liff_settings',
                'project_feature_flags',
                'project_admin_ui_settings',
                'project_profile_smaregi_settings',
                'project_member_points_settings',
                'project_member_rank_settings',
                'project_wallet_settings',
                'project_announcement_settings',
            ];
            foreach ($tables as $t) {
                if (DB::table($t)->where('project_id', $pid)->exists()) {
                    continue;
                }
                $defaults->project_id = $pid;
                if ($t === 'project_business_settings') {
                    $this->insertBusinessSettings($pid, $defaults, $now);
                } elseif ($t === 'project_line_liff_settings') {
                    $this->insertLineLiffSettings($pid, $defaults, $now);
                } elseif ($t === 'project_feature_flags') {
                    $this->insertFeatureFlags($pid, $defaults, $now);
                } elseif ($t === 'project_admin_ui_settings') {
                    $this->insertAdminUiSettings($pid, $defaults, $now);
                } elseif ($t === 'project_profile_smaregi_settings') {
                    $this->insertProfileSmaregiSettings($pid, $defaults, $now);
                } elseif ($t === 'project_member_points_settings') {
                    $this->insertMemberPointsSettings($pid, $defaults, $now);
                } elseif ($t === 'project_member_rank_settings') {
                    $this->insertMemberRankSettings($pid, $defaults, $now);
                } elseif ($t === 'project_wallet_settings') {
                    $this->insertWalletSettings($pid, $defaults, $now);
                } elseif ($t === 'project_announcement_settings') {
                    $this->insertAnnouncementSettings($pid, $defaults, $now);
                }
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'project_announcement_settings',
            'project_wallet_settings',
            'project_member_rank_settings',
            'project_member_points_settings',
            'project_profile_smaregi_settings',
            'project_admin_ui_settings',
            'project_feature_flags',
            'project_line_liff_settings',
            'project_business_settings',
        ];
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
