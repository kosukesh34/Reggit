<?php

namespace App\Services\Subscription;

use App\Domain\Settings\Repositories\FeatureSettings;
use App\Models\MemberSubscription;
use App\Models\Product;
use App\Models\Project;
use App\Models\Settings\ProjectPaymentSetting;
use App\Models\User;
use App\Services\Member\CurrentMemberPrincipal;
use App\Util\StoreContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberSubscriptionService
{
    public function __construct(
        private SubscriptionGateway $gateway,
        private CurrentMemberPrincipal $principals,
        private StoreContext $storeContext,
        private FeatureSettings $features,
    ) {}

    /** Stripe Checkout の URL を返す。サブスクは決済確定の Webhook で作成する。 */
    public function startCheckout(User $user, int $productId): string
    {
        $projectId = (int) ($user->project_id ?? 0);
        // 外部 API 呼び出しはトランザクションの外で行う
        [$memberId, $product, $setting] = DB::transaction(function () use ($user, $projectId, $productId): array {
            $member = $this->principals->lock($user, $projectId)?->member;
            if ($member === null) {
                $this->fail('サブスクの申し込みには会員登録が必要です');
            }
            if (! $this->features->isFeatureEnabled('feature_tickets_enabled', $projectId)) {
                $this->fail('サブスクは現在申し込めません');
            }
            $product = Product::where('project_id', $projectId)->where('subscription_enabled', true)
                ->where('is_available', true)->find($productId);
            if ($product === null) {
                $this->fail('サブスクが見つかりません');
            }
            $setting = ProjectPaymentSetting::current($projectId);
            if ($setting === null || ! in_array('card', $setting->methods, true)) {
                $this->fail('サブスクはクレジットカード決済の設定が必要です');
            }
            if (MemberSubscription::where('project_id', $projectId)->where('member_id', $member->id)
                ->where('product_id', $product->id)->whereNotIn('status', MemberSubscription::ENDED_STATUSES)->exists()) {
                $this->fail('このサブスクはすでに申し込み済みです');
            }

            return [(int) $member->id, $product, $setting];
        });

        $project = Project::findOrFail($projectId);
        $returnUrl = rtrim((string) config('app.url'), '/').'/mobile/payment/return?surface=web&subscription=1&project_key='.rawurlencode($project->project_key);

        return $this->gateway->createCheckout($setting, $product->name, (int) $product->price, [
            'project_id' => (string) $projectId,
            'payment_setting_id' => (string) $setting->id,
            'member_id' => (string) $memberId,
            'product_id' => (string) $product->id,
            'store_id' => (string) $this->storeContext->resolveForWrite($projectId),
        ], $returnUrl.'&status=success', $returnUrl.'&status=cancel');
    }

    /** 会員本人の解約。現在の請求期間の終わりで終了する。 */
    public function cancelForMember(User $user, int $subscriptionId): MemberSubscription
    {
        $projectId = (int) ($user->project_id ?? 0);

        return DB::transaction(function () use ($user, $projectId, $subscriptionId): MemberSubscription {
            $member = $this->principals->lock($user, $projectId)?->member;
            if ($member === null) {
                $this->fail('会員登録が必要です');
            }
            $subscription = MemberSubscription::where('project_id', $projectId)->where('member_id', $member->id)
                ->lockForUpdate()->find($subscriptionId);
            if ($subscription === null) {
                $this->fail('サブスクが見つかりません');
            }

            return $this->cancel($subscription);
        });
    }

    public function cancel(MemberSubscription $subscription): MemberSubscription
    {
        if ($subscription->isEnded() || $subscription->cancel_at_period_end) {
            return $subscription;
        }
        $setting = ProjectPaymentSetting::where('project_id', $subscription->project_id)->findOrFail($subscription->payment_setting_id);
        $this->gateway->cancelAtPeriodEnd($setting, $subscription->stripe_subscription_id);
        $subscription->cancel_at_period_end = true;
        $subscription->save();

        return $subscription;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['subscription' => [$message]]);
    }
}
