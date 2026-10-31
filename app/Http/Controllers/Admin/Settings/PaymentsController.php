<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\UseCases\Admin\Settings\ProjectPaymentSettingsUseCase;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentsController extends AdminBaseController
{
    public function __construct(private ProjectPaymentSettingsUseCase $settings) {}

    public function index()
    {
        $setting = $this->settings->show($this->resolveAdminProjectId());

        return response()->view('admin.settings.payments', [
            'methods' => $setting['methods'],
            'accountId' => $setting['accountId'],
            'publishableKey' => $setting['publishableKey'],
            'hasSecret' => $setting['hasSecret'],
            'webhookUrl' => $setting['settingId'] === null ? null : route('payment.project.webhook', [
                'project_key' => $this->resolveAdminProjectKey(), 'setting' => $setting['settingId'],
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
        $this->settings->save($this->resolveAdminProjectId(), $input);

        return redirect()->route('admin.settings.payments', $this->buildAdminRouteParams())
            ->with('success', '決済設定を保存しました。表示されたWebhook URLをStripeに登録してください。');
    }
}
