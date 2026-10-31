<?php

namespace App\UseCases\Payment;

use App\Enums\OrderType;
use App\Models\Order;
use App\Models\Project;
use App\Models\Settings\ProjectLineLiffSetting;

/** Web 決済（Stripe Checkout）から戻ったときに、決済を始めた画面（LIFF・ショップ）へ戻す URL を決める。 */
class PaymentReturnUrlUseCase
{
    public function forWeb(string $projectKey, string $status, int $orderId, bool $subscription, string $requestHost): string
    {
        $project = Project::where('project_key', $projectKey)->firstOrFail();
        if ($subscription) {
            return $this->liffUrl($project).'?subscription_status='.$status.'#/tickets';
        }
        $order = Order::withoutGlobalScopes()->where('project_id', $project->id)->findOrFail($orderId);

        if ($order->order_type === OrderType::SHIPPING) {
            // 決済開始時と同じホストへ戻る（独自ドメインなら /shop、それ以外は /web/{key}/shop）
            $domain = strtolower(trim((string) $project->custom_shop_domain));

            return ($domain !== '' && strtolower($requestHost) === $domain
                ? 'https://'.$domain.'/shop'
                : route('shop.show', ['project_key' => $project->project_key]))
                .'?payment_order_id='.$order->id.'&payment_status='.$status;
        }

        return $this->liffUrl($project).'?payment_order_id='.$order->id.'&payment_status='.$status
            .($order->order_type === OrderType::TICKET ? '#/tickets' : '#/menu');
    }

    private function liffUrl(Project $project): string
    {
        $liffId = ProjectLineLiffSetting::find($project->id)?->line_liff_id;

        return filled($liffId) ? 'https://liff.line.me/'.rawurlencode($liffId) : url('/liff/'.rawurlencode($project->project_key));
    }
}
