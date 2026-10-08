<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Models\Project;
use App\Models\Settings\ProjectPaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Stripe\StripeClient;

class PaymentsController extends AdminBaseController
{
    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $setting = ProjectPaymentSetting::current($projectId);

        return response()->view('admin.settings.payments', [
            'methods' => $setting?->methods ?? ['cash'],
            'accountId' => $setting?->account_id,
            'publishableKey' => $setting?->publishable_key ?? '',
            'hasSecret' => $setting !== null,
            'webhookUrl' => $setting === null ? null : route('payment.project.webhook', [
                'project_key' => $this->resolveAdminProjectKey(), 'setting' => $setting->id,
            ]),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request)
    {
        $input = $request->validate([
            'publishable_key' => ['required', 'string', 'regex:/^pk_(test|live)_[A-Za-z0-9]+$/'],
            'secret_key' => ['nullable', 'string', 'regex:/^[sr]k_(test|live)_[A-Za-z0-9]+$/'],
            'webhook_secret' => ['nullable', 'string', 'regex:/^whsec_[A-Za-z0-9]+$/'],
            'methods' => ['required', 'array', 'min:1'],
            'methods.*' => ['string', 'distinct', Rule::in(['card', 'paypay', 'apple_pay', 'google_pay', 'cash'])],
        ]);
        $projectId = $this->resolveAdminProjectId();
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

        return redirect()->route('admin.settings.payments', $this->buildAdminRouteParams())
            ->with('success', '決済設定を保存しました。表示されたWebhook URLをStripeに登録してください。');
    }
}
