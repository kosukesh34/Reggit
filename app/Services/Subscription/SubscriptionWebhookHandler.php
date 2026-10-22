<?php

namespace App\Services\Subscription;

use App\Domain\Order\Events\OrderPointsSyncRequested;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Order\ValueObjects\OrderLineItem;
use App\Domain\Order\ValueObjects\PendingOrder;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\MemberTicket;
use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\Store;
use App\Services\OrderNumberAssigner;
use App\Services\OrderService;
use App\Services\PostCommitDomainEventDispatcher;
use App\Util\AdminContext;
use App\Util\LogHelper;
use App\Util\StoreContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Stripe\Event;
use Stripe\Webhook;

/**
 * サブスクの Stripe Webhook。毎月の支払いごとに注文（スマレジ送信用）と当月分の回数券を発行する。
 */
class SubscriptionWebhookHandler
{
    private const SUBSCRIPTION_EVENTS = ['customer.subscription.updated', 'customer.subscription.deleted'];

    public function __construct(
        private OrderRepository $orders,
        private OrderNumberAssigner $orderNumberAssigner,
        private OrderService $orderService,
        private PostCommitDomainEventDispatcher $postCommitEvents,
        private StoreContext $storeContext,
    ) {}

    /** 署名検証前の種別判定。サブスクのイベントだけをこのハンドラーへ回す。 */
    public static function handles(string $payload): bool
    {
        $type = json_decode($payload, true)['type'] ?? null;

        return $type === 'invoice.paid' || in_array($type, self::SUBSCRIPTION_EVENTS, true);
    }

    public function handle(string $payload, string $sigHeader, string $secret, int $projectId, int $settingId): void
    {
        $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        if ($event->type === 'invoice.paid') {
            $this->handleInvoicePaid($event, $projectId, $settingId);
        } elseif (in_array($event->type, self::SUBSCRIPTION_EVENTS, true)) {
            $this->syncSubscription($event, $projectId, $settingId);
        }
    }

    private function handleInvoicePaid(Event $event, int $projectId, int $settingId): void
    {
        $invoice = $event->data->object;
        $details = $invoice->parent?->subscription_details;
        $stripeSubscriptionId = is_string($details?->subscription) ? $details->subscription : (string) ($details?->subscription?->id ?? '');
        $metadata = $details?->metadata?->toArray() ?? [];
        if ($stripeSubscriptionId === '' || ($invoice->currency ?? '') !== 'jpy'
            || (int) ($metadata['project_id'] ?? 0) !== $projectId || (int) ($metadata['payment_setting_id'] ?? 0) !== $settingId) {
            return;
        }
        // 退会済み会員には発行しない（返金は Stripe 側で扱う）
        $member = Member::withoutGlobalScopes()->whereNull('deleted_at')->where('project_id', $projectId)->find((int) ($metadata['member_id'] ?? 0));
        $product = Product::withoutGlobalScopes()->where('project_id', $projectId)->find((int) ($metadata['product_id'] ?? 0));
        $store = Store::where('project_id', $projectId)->find((int) ($metadata['store_id'] ?? 0));
        if ($member === null || $member->user_id === null || $product === null || $store === null) {
            LogHelper::warning('[Subscription] invoice.paid target missing', ['project_id' => $projectId, 'invoice' => $invoice->id]);

            return;
        }
        $periodEnd = $invoice->lines->data[0]->period->end ?? null;

        $previousProject = AdminContext::getCurrentProject();
        AdminContext::setCurrentProject(Project::findOrFail($projectId));
        try {
            $order = $this->storeContext->runForStore($store, fn (): ?Order => DB::transaction(
                fn (): ?Order => $this->recordPaidPeriod($invoice->id, (int) $invoice->amount_paid, $stripeSubscriptionId, $periodEnd, $member, $product, $store, $settingId),
            ));
            if ($order !== null) {
                $this->postCommitEvents->dispatch(new OrderPointsSyncRequested((int) $order->id, $projectId, (int) $order->user_id));
            }
        } finally {
            AdminContext::setCurrentProject($previousProject);
        }
    }

    private function recordPaidPeriod(string $invoiceId, int $amount, string $stripeSubscriptionId, ?int $periodEnd, Member $member, Product $product, Store $store, int $settingId): ?Order
    {
        $projectId = (int) $member->project_id;
        $subscription = MemberSubscription::withoutGlobalScopes()->where('stripe_subscription_id', $stripeSubscriptionId)->lockForUpdate()->first()
            ?? MemberSubscription::withoutGlobalScopes()->create([
                'project_id' => $projectId,
                'member_id' => $member->id,
                'product_id' => $product->id,
                'store_id' => $store->id,
                'payment_setting_id' => $settingId,
                'stripe_subscription_id' => $stripeSubscriptionId,
                'name' => $product->name,
                'status' => 'active',
            ]);
        if ((int) $subscription->project_id !== $projectId || Order::withoutGlobalScopes()->where('stripe_invoice_id', $invoiceId)->exists()) {
            return null;
        }

        $orderId = $this->orders->persistPending(
            new PendingOrder($projectId, (int) $member->user_id, OrderType::SUBSCRIPTION, PaymentMethod::STRIPE, $amount, 0, null, (int) $store->id),
            [new OrderLineItem((int) $product->id, 1, (float) $amount, null, $product->name)],
        );
        $order = Order::withoutGlobalScopes()->findOrFail($orderId);
        $order->forceFill([
            'status' => OrderStatus::COMPLETED,
            'payment_brand' => 'card',
            'payment_setting_id' => $settingId,
            'stripe_invoice_id' => $invoiceId,
            'smaregi_create_pending' => true,
            'smaregi_create_retry_at' => now(),
        ])->save();
        $this->orderNumberAssigner->assignIfMissing($orderId, $projectId);

        $expiresAt = $periodEnd !== null ? Carbon::createFromTimestamp($periodEnd, config('app.timezone')) : now()->addMonth();
        MemberTicket::withoutGlobalScopes()->create([
            'project_id' => $projectId,
            'member_id' => $member->id,
            'member_subscription_id' => $subscription->id,
            'order_id' => $orderId,
            'order_item_id' => $order->items()->value('id'),
            'product_id' => $product->id,
            'name' => $product->name,
            'total_uses' => $product->subscription_monthly_uses,
            'daily_limit' => $product->daily_limit,
            'expires_at' => $expiresAt,
        ]);
        $subscription->forceFill(['status' => 'active', 'current_period_end' => $expiresAt])->save();

        $this->orderService->applyStripePaymentRewardsIfNeeded($order->refresh());

        return $order;
    }

    private function syncSubscription(Event $event, int $projectId, int $settingId): void
    {
        $remote = $event->data->object;
        $periodEnd = $remote->items->data[0]->current_period_end ?? null;
        MemberSubscription::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->where('payment_setting_id', $settingId)
            ->where('stripe_subscription_id', $remote->id)
            ->update(array_filter([
                'status' => (string) $remote->status,
                'cancel_at_period_end' => (bool) $remote->cancel_at_period_end,
                'current_period_end' => $periodEnd !== null ? Carbon::createFromTimestamp($periodEnd, config('app.timezone')) : null,
                'updated_at' => now(),
            ], fn ($value) => $value !== null));
    }
}
