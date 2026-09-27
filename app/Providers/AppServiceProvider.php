<?php

namespace App\Providers;

use App\Domain\Coupon\Repositories\CouponDeliveryTargetRepository;
use App\Domain\Coupon\Repositories\MemberCouponRepository;
use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Order\Repositories\ProductCatalogRepository;
use App\Domain\Order\Repositories\ProductStockRepository;
use App\Domain\Queue\Repositories\QueueEntryRepository;
use App\Domain\Reservation\Repositories\ReservationRepository;
use App\Domain\Settings\Repositories\AdminAppearanceSettings;
use App\Domain\Settings\Repositories\AppConfigSettings;
use App\Domain\Settings\Repositories\FrontendShellSettings;
use App\Domain\Settings\Repositories\HomeSettingsRepository;
use App\Domain\Settings\Repositories\MemberCardPreviewSettings;
use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Infrastructure\Events\LoggingDomainEventListener;
use App\Infrastructure\Events\MulticastDomainEventDispatcher;
use App\Infrastructure\Events\NotifyOnOrderConfirmedListener;
use App\Infrastructure\Events\NotifyOnOrderReadyListener;
use App\Infrastructure\Events\SyncOrderPointsToSmaregiListener;
use App\Infrastructure\Events\SyncOrderTransactionToSmaregiListener;
use App\Infrastructure\Events\SyncPointsEarnedToSmaregiListener;
use App\Infrastructure\Persistence\Eloquent\Coupon\EloquentCouponDeliveryTargetRepository;
use App\Infrastructure\Persistence\Eloquent\Coupon\EloquentMemberCouponRepository;
use App\Infrastructure\Persistence\Eloquent\Member\EloquentMemberRepository;
use App\Infrastructure\Persistence\Eloquent\Order\EloquentOrderRepository;
use App\Infrastructure\Persistence\Eloquent\Order\EloquentProductCatalogRepository;
use App\Infrastructure\Persistence\Eloquent\Order\EloquentProductStockRepository;
use App\Infrastructure\Persistence\Eloquent\Product\EloquentProductReadRepository;
use App\Infrastructure\Persistence\Eloquent\Queue\EloquentQueueEntryRepository;
use App\Infrastructure\Persistence\Eloquent\Reservation\EloquentReservationRepository;
use App\Infrastructure\Persistence\Eloquent\Settings\EloquentShopSettingRepository;
use App\Models\Announcement;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\GalleryCategory;
use App\Models\GalleryItem;
use App\Models\MemberCoupon;
use App\Models\MemberLotteryEntitlement;
use App\Models\MemberStamp;
use App\Models\MemberStampTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use App\Models\Project;
use App\Models\QueueEntry;
use App\Models\Reservation;
use App\Observers\MemberCouponObserver;
use App\Observers\MemberLotteryEntitlementObserver;
use App\Observers\MemberStampObserver;
use App\Observers\PointTransactionObserver;
use App\Observers\ProjectDataRealtimeObserver;
use App\Observers\ProjectObserver;
use App\Repositories\Contracts\AdminUserRepository;
use App\Repositories\Contracts\ProjectRepository;
use App\Repositories\Contracts\UserDeviceRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Eloquent\EloquentAdminUserRepository;
use App\Repositories\Eloquent\EloquentProjectRepository;
use App\Repositories\Eloquent\EloquentUserDeviceRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Services\Smaregi\SmaregiApiClient;
use App\UseCases\Payment\PaymentGateway;
use App\UseCases\Payment\StripePaymentGateway;
use App\UseCases\Product\Contracts\ProductReadRepository;
use App\Util\TenantContext;
use App\View\Composers\AdminLayoutComposer;
use App\View\Composers\FrontendShellComposer;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);
        $this->app->bind(ProjectRepository::class, EloquentProjectRepository::class);
        $this->app->bind(AdminUserRepository::class, EloquentAdminUserRepository::class);
        $this->app->bind(UserDeviceRepository::class, EloquentUserDeviceRepository::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(MemberRepository::class, EloquentMemberRepository::class);
        $this->app->bind(OrderRepository::class, EloquentOrderRepository::class);
        $this->app->bind(ProductStockRepository::class, EloquentProductStockRepository::class);
        $this->app->bind(ProductCatalogRepository::class, EloquentProductCatalogRepository::class);
        $this->app->bind(ProductReadRepository::class, EloquentProductReadRepository::class);
        $this->app->bind(MemberCouponRepository::class, EloquentMemberCouponRepository::class);
        $this->app->bind(CouponDeliveryTargetRepository::class, EloquentCouponDeliveryTargetRepository::class);
        $this->app->bind(ReservationRepository::class, EloquentReservationRepository::class);
        $this->app->bind(QueueEntryRepository::class, EloquentQueueEntryRepository::class);
        $this->app->bind(ShopSettingRepository::class, EloquentShopSettingRepository::class);
        $this->app->bind(AdminAppearanceSettings::class, EloquentShopSettingRepository::class);
        $this->app->bind(AppConfigSettings::class, EloquentShopSettingRepository::class);
        $this->app->bind(FrontendShellSettings::class, EloquentShopSettingRepository::class);
        $this->app->bind(HomeSettingsRepository::class, EloquentShopSettingRepository::class);
        $this->app->bind(MemberCardPreviewSettings::class, EloquentShopSettingRepository::class);
        $this->app->singleton(DomainEventDispatcher::class, function ($app) {
            return new MulticastDomainEventDispatcher([
                $app->make(LoggingDomainEventListener::class),
                $app->make(NotifyOnOrderConfirmedListener::class),
                $app->make(NotifyOnOrderReadyListener::class),
                $app->make(SyncPointsEarnedToSmaregiListener::class),
                $app->make(SyncOrderPointsToSmaregiListener::class),
                $app->make(SyncOrderTransactionToSmaregiListener::class),
            ]);
        });

        $this->app->singleton(PaymentGateway::class, function () {
            $secret = (string) config('services.stripe.secret', '');

            return new StripePaymentGateway($secret);
        });

        $this->app->singleton(Client::class, function () {
            return new Client(['timeout' => 15]);
        });

        // Service と Resolver で同一インスタンスを共有しつつ、リクエスト／ジョブ単位で破棄する
        $this->app->scoped(SmaregiApiClient::class);
    }

    public function boot(): void
    {
        Project::observe(ProjectObserver::class);
        MemberStamp::observe(MemberStampObserver::class);
        MemberCoupon::observe(MemberCouponObserver::class);
        PointTransaction::observe(PointTransactionObserver::class);
        MemberLotteryEntitlement::observe(MemberLotteryEntitlementObserver::class);
        foreach ([
            Announcement::class,
            Coupon::class,
            CouponRedemption::class,
            GalleryCategory::class,
            GalleryItem::class,
            MemberCoupon::class,
            MemberStamp::class,
            MemberStampTransaction::class,
            Order::class,
            OrderItem::class,
            PointTransaction::class,
            Product::class,
            ProductOption::class,
            ProductOptionGroup::class,
            QueueEntry::class,
            Reservation::class,
        ] as $realtimeModel) {
            $realtimeModel::observe(ProjectDataRealtimeObserver::class);
        }
        require base_path('routes/channels.php');

        if ($this->app->runningInConsole() === false) {
            if (request()->isSecure() ||
                request()->header('X-Forwarded-Proto') === 'https' ||
                request()->header('X-Forwarded-Ssl') === 'on') {
                URL::forceScheme('https');
            }
        }

        View::composer('admin.layout', AdminLayoutComposer::class);
        View::composer(['index', 'frontend.line', 'frontend.native'], FrontendShellComposer::class);
    }
}
