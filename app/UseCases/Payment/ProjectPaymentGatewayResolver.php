<?php

namespace App\UseCases\Payment;

use App\Models\Order;
use App\Models\Project;
use App\Models\Settings\ProjectPaymentSetting;
use InvalidArgumentException;

class ProjectPaymentGatewayResolver
{
    public function forOrder(Order $order, PaymentGateway $legacy): PaymentGateway
    {
        if ($order->payment_setting_id !== null) {
            $setting = ProjectPaymentSetting::where('project_id', $order->project_id)
                ->findOrFail($order->payment_setting_id);

            // Alternative gateway implementations manage their own credentials.
            if (! $legacy instanceof StripePaymentGateway) {
                return $legacy;
            }

            return new StripePaymentGateway($setting->secret_key, $order->payment_brand ?? 'card', [
                'project_id' => (string) $order->project_id,
                'payment_setting_id' => (string) $setting->id,
            ]);
        }

        if ($legacy instanceof StripePaymentGateway) {
            $defaultKey = config('app.default_project_key') ?: 'default';
            if (Project::find($order->project_id)?->project_key !== $defaultKey) {
                throw new InvalidArgumentException('このプロジェクトの決済設定を登録してください');
            }
        }

        return $legacy;
    }

    /** Only registered methods are exposed; preview brands are never advertised as usable. */
    public function methods(int $projectId): array
    {
        $setting = ProjectPaymentSetting::current($projectId);
        if ($setting !== null) {
            return array_values(array_intersect($setting->methods, ['card', 'paypay', 'apple_pay', 'google_pay', 'cash']));
        }

        $defaultKey = config('app.default_project_key') ?: 'default';

        return Project::find($projectId)?->project_key === $defaultKey
            && filled(config('services.stripe.secret')) ? ['card', 'cash'] : ['cash'];
    }

    /** 決済画面（Stripe.js）に渡す公開キー。プロジェクトの決済設定がなければ共通設定 */
    public function publishableKey(int $projectId): string
    {
        return ProjectPaymentSetting::current($projectId)?->publishable_key ?? (string) config('services.stripe.key');
    }

    /** 注文作成時の決済設定の公開キー。設定なしで作られた注文は共通設定 */
    public function publishableKeyForOrder(Order $order): ?string
    {
        return $order->payment_setting_id === null ? config('services.stripe.key')
            : ProjectPaymentSetting::where('project_id', $order->project_id)->findOrFail($order->payment_setting_id)->publishable_key;
    }

    /** Webhook URL に含まれる決済設定。他プロジェクトの設定は 404 */
    public function webhookSetting(int $projectId, int|string $settingId): ProjectPaymentSetting
    {
        return ProjectPaymentSetting::where('project_id', $projectId)->findOrFail($settingId);
    }
}
