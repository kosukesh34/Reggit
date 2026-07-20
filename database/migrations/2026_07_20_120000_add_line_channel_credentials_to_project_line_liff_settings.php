<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_line_liff_settings', function (Blueprint $table) {
            $table->text('line_channel_access_token')->nullable()->after('line_liff_url');
            $table->text('line_channel_secret')->nullable()->after('line_channel_access_token');
        });

        $this->seedEnvCredentialsIntoFormerDefaultProject();
    }

    public function down(): void
    {
        Schema::table('project_line_liff_settings', function (Blueprint $table) {
            $table->dropColumn(['line_channel_access_token', 'line_channel_secret']);
        });
    }

    private function seedEnvCredentialsIntoFormerDefaultProject(): void
    {
        $projectId = $this->resolveFormerDefaultProjectId();
        if ($projectId === null) {
            return;
        }

        $liffId = trim((string) (env('LINE_LIFF_ID') ?: ''));
        $liffUrl = trim((string) (env('LINE_LIFF_URL') ?: ''));
        $accessToken = trim((string) (env('LINE_CHANNEL_ACCESS_TOKEN') ?: ''));
        $channelSecret = trim((string) (env('LINE_CHANNEL_SECRET') ?: ''));

        $row = DB::table('project_line_liff_settings')->where('project_id', $projectId)->first();
        $payload = [
            'updated_at' => now(),
        ];

        if ($row === null) {
            $payload['project_id'] = $projectId;
            $payload['created_at'] = now();
            if ($liffId !== '') {
                $payload['line_liff_id'] = $liffId;
            }
            if ($liffUrl !== '') {
                $payload['line_liff_url'] = $liffUrl;
            }
            if ($accessToken !== '') {
                $payload['line_channel_access_token'] = Crypt::encryptString($accessToken);
            }
            if ($channelSecret !== '') {
                $payload['line_channel_secret'] = Crypt::encryptString($channelSecret);
            }
            DB::table('project_line_liff_settings')->insert($payload);

            return;
        }

        $currentLiffId = trim((string) ($row->line_liff_id ?? ''));
        $currentLiffUrl = trim((string) ($row->line_liff_url ?? ''));
        $currentToken = trim((string) ($row->line_channel_access_token ?? ''));
        $currentSecret = trim((string) ($row->line_channel_secret ?? ''));

        if ($currentLiffId === '' && $liffId !== '') {
            $payload['line_liff_id'] = $liffId;
        }
        if ($currentLiffUrl === '' && $liffUrl !== '') {
            $payload['line_liff_url'] = $liffUrl;
        }
        if ($currentToken === '' && $accessToken !== '') {
            $payload['line_channel_access_token'] = Crypt::encryptString($accessToken);
        }
        if ($currentSecret === '' && $channelSecret !== '') {
            $payload['line_channel_secret'] = Crypt::encryptString($channelSecret);
        }

        if (count($payload) > 1) {
            DB::table('project_line_liff_settings')->where('project_id', $projectId)->update($payload);
        }
    }

    private function resolveFormerDefaultProjectId(): ?int
    {
        $byKey = DB::table('projects')->where('project_key', 'sb_skp835t6')->value('id');
        if (is_numeric($byKey)) {
            return (int) $byKey;
        }

        $byLegacyKey = DB::table('projects')->where('project_key', 'default')->value('id');
        if (is_numeric($byLegacyKey)) {
            return (int) $byLegacyKey;
        }

        $byId = DB::table('projects')->where('id', 1)->value('id');

        return is_numeric($byId) ? (int) $byId : null;
    }
};
