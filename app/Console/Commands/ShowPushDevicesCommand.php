<?php

namespace App\Console\Commands;

use App\Models\UserDevice;
use Illuminate\Console\Command;

class ShowPushDevicesCommand extends Command
{
    protected $signature = 'push:show-devices
                            {--full : push_token を全文表示（OneSignal で検索用）}';

    protected $description = 'user_devices の push_token 登録一覧を表示（tinker 不要）';

    public function handle(): int
    {
        $devices = UserDevice::query()
            ->whereNotNull('push_token')
            ->where('push_token', '!=', '')
            ->orderBy('last_used_at', 'desc')
            ->get(['id', 'project_id', 'user_id', 'platform', 'push_token', 'last_used_at']);

        if ($devices->isEmpty()) {
            $this->info('push_token が登録された端末は 0 件です。');
            return self::SUCCESS;
        }

        $this->info('push_token 登録端末: ' . $devices->count() . ' 件');
        $showFull = $this->option('full');

        foreach ($devices as $d) {
            $token = trim((string) $d->push_token);
            $preview = $showFull ? $token : (strlen($token) > 16 ? substr($token, 0, 8) . '...' . substr($token, -4) : $token);
            $this->line(sprintf(
                '  id=%d project_id=%d user_id=%d platform=%s push_token=%s last_used=%s',
                $d->id,
                $d->project_id,
                $d->user_id,
                $d->platform ?? '-',
                $preview,
                $d->last_used_at !== null ? $d->last_used_at->format('Y-m-d H:i') : '-'
            ));
        }

        if (! $showFull && $devices->isNotEmpty()) {
            $this->newLine();
            $this->comment('OneSignal で ID 検索する場合: php artisan push:show-devices --full');
        }

        return self::SUCCESS;
    }
}
