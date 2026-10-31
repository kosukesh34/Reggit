<?php

namespace App\UseCases\Admin\Settings;

use App\Models\Project;
use App\Models\Settings\ProjectPaymentSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Stripe\StripeClient;

/** プロジェクトごとの Stripe 決済設定。保存は履歴として追加し、最新を有効にする。 */
class ProjectPaymentSettingsUseCase
{
    /** @return array{methods: list<string>, accountId: ?string, publishableKey: string, hasSecret: bool, settingId: ?int} */
    public function show(int $projectId): array
    {
        $setting = ProjectPaymentSetting::current($projectId);

        return [
            'methods' => $setting?->methods ?? ['cash'],
            'accountId' => $setting?->account_id,
            'publishableKey' => $setting?->publishable_key ?? '',
            'hasSecret' => $setting !== null,
            'settingId' => $setting?->id,
        ];
    }

    /**
     * 秘密キーは未入力なら前回の値を引き継ぎ、Stripe でアカウントを確認してから保存する。
     *
     * @param  array{publishable_key: string, secret_key?: ?string, webhook_secret?: ?string, methods: list<string>}  $input
     *
     * @throws ValidationException
     */
    public function save(int $projectId, array $input): void
    {
        DB::transaction(function () use ($projectId, $input) {
            Project::whereKey($projectId)->lockForUpdate()->firstOrFail();
            $previous = ProjectPaymentSetting::current($projectId);
            $secret = $input['secret_key'] ?? $previous?->secret_key;
            $webhookSecret = $input['webhook_secret'] ?? $previous?->webhook_secret;
            if (! filled($secret) || ! filled($webhookSecret)) {
                throw ValidationException::withMessages(['secret_key' => '初回は秘密キーとWebhook署名キーを登録してください']);
            }
            if (str_contains($secret, '_test_') !== str_contains($input['publishable_key'], '_test_')) {
                throw ValidationException::withMessages(['publishable_key' => '公開キーと秘密キーのテスト・本番を揃えてください']);
            }
            try {
                $account = (new StripeClient($secret))->accounts->retrieve();
            } catch (\Throwable) {
                throw ValidationException::withMessages(['secret_key' => 'Stripeアカウントを確認できませんでした。キーと権限を確認してください']);
            }
            if ($previous !== null && $previous->account_id !== $account->id && empty($input['webhook_secret'])) {
                throw ValidationException::withMessages(['webhook_secret' => 'アカウント変更時は新しいWebhook署名キーも登録してください']);
            }
            ProjectPaymentSetting::create([
                'project_id' => $projectId,
                'account_id' => $account->id,
                'publishable_key' => $input['publishable_key'],
                'secret_key' => $secret,
                'webhook_secret' => $webhookSecret,
                'methods' => $input['methods'],
            ]);
        });
    }
}
