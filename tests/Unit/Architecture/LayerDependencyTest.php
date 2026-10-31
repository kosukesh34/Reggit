<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class LayerDependencyTest extends TestCase
{
    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function forbiddenDependencies(): iterable
    {
        yield 'domain remains independent from outer layers and frameworks' => [
            dirname(__DIR__, 3).'/app/Domain',
            [
                'App\\Http\\',
                'App\\Infrastructure\\',
                'App\\Models\\',
                'App\\Services\\',
                'App\\UseCases\\',
                'Illuminate\\',
            ],
        ];

        yield 'services do not depend on use cases or controllers' => [
            dirname(__DIR__, 3).'/app/Services',
            [
                'App\\Http\\Controllers\\',
                'App\\UseCases\\',
            ],
        ];

        yield 'use cases do not depend on controllers or HTTP requests' => [
            dirname(__DIR__, 3).'/app/UseCases',
            [
                'App\\Http\\Controllers\\',
                'Illuminate\\Http\\Request',
            ],
        ];
    }

    public function test_refactored_admin_controllers_do_not_import_models(): void
    {
        $controllers = [
            'Admin/HelpController.php',
            'Admin/AnalysisDeliveryController.php',
            'Admin/ReservationController.php',
            'Admin/SystemHelpArticleController.php',
            'Admin/SurveyController.php',
            'Admin/PromoPopupController.php',
            'Admin/AcquisitionSourceController.php',
            'SurveyController.php',
            'PromoPopupController.php',
        ];
        $violations = [];
        foreach ($controllers as $controller) {
            $path = dirname(__DIR__, 3).'/app/Http/Controllers/'.$controller;
            $source = file_get_contents($path);
            if (str_contains($source, 'use App\\Models\\')) {
                $violations[] = $controller;
            }
        }

        self::assertSame([], $violations);
    }

    public function test_refactored_public_controllers_do_not_import_models(): void
    {
        $controllers = [
            'LegalDocumentController.php',
            'StampGrantCodeController.php',
            'HealthController.php',
            'Api/V1/DeviceOnceCouponController.php',
            'Admin/MemberTransferController.php',
            'Admin/Account/AccountDestroyController.php',
            'Admin/Account/AccountFormController.php',
            'Admin/Account/AccountIndexController.php',
            'Admin/Settings/StaffController.php',
            'Admin/Settings/StaffMutationController.php',
            'Admin/AdminBaseController.php',
            'Admin/AdminRootController.php',
            'Admin/Auth/SystemAdminLoginController.php',
            'Member/MemberTransferClaimController.php',
            'Admin/StoreController.php',
            'Admin/ChatController.php',
            'Admin/SystemNoticeController.php',
            'Api/StoreAppAuthController.php',
        ];
        $violations = [];

        foreach ($controllers as $controller) {
            $path = dirname(__DIR__, 3).'/app/Http/Controllers/'.$controller;
            $source = file_get_contents($path);
            if (str_contains($source, 'use App\\Models\\') || str_contains($source, 'use Illuminate\\Support\\Facades\\DB')) {
                $violations[] = $controller;
            }
        }

        self::assertSame([], $violations);
    }

    public function test_view_composers_depend_on_focused_settings_contracts(): void
    {
        $directory = dirname(__DIR__, 3).'/app/View/Composers';
        $violations = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            if (str_contains($source, 'ShopSettingRepository')) {
                $violations[] = $file->getPathname().' imports the broad settings repository';
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    public function test_app_config_depends_on_its_focused_settings_contract(): void
    {
        $source = file_get_contents(dirname(__DIR__, 3).'/app/Services/AppConfigService.php');

        self::assertStringContainsString('use App\\Domain\\Settings\\Repositories\\AppConfigSettings;', $source);
        self::assertStringNotContainsString('ShopSettingRepository', $source);
    }

    public function test_home_settings_use_cases_depend_on_their_focused_contract(): void
    {
        foreach (['GetHomeSettingsUseCase.php', 'UpdateHomeSettingsUseCase.php'] as $file) {
            $source = file_get_contents(dirname(__DIR__, 3).'/app/UseCases/Admin/Settings/'.$file);

            self::assertStringContainsString('use App\\Domain\\Settings\\Repositories\\HomeSettingsRepository;', $source);
            self::assertStringNotContainsString('ShopSettingRepository', $source);
        }
    }

    public function test_queue_use_cases_depend_on_their_focused_settings_contract(): void
    {
        $root = dirname(__DIR__, 3).'/app/';
        $files = [
            $root.'UseCases/Queue/QueueOpenUseCase.php',
            $root.'UseCases/Queue/QueueStatsService.php',
            $root.'UseCases/Queue/QueueStoreUseCase.php',
            $root.'UseCases/Admin/Settings/GetQueueSettingsUseCase.php',
            $root.'UseCases/Admin/Settings/UpdateQueueSettingsUseCase.php',
        ];

        foreach ($files as $file) {
            $source = file_get_contents($file);
            self::assertIsString($source);
            self::assertStringContainsString('QueueSettings', $source, $file);
            self::assertStringNotContainsString('ShopSettingRepository', $source, $file);
        }
    }

    public function test_reservation_services_depend_on_their_focused_settings_contract(): void
    {
        $root = dirname(__DIR__, 3).'/app/';

        foreach ([
            $root.'UseCases/Reservation/ReservationAvailabilityService.php',
            $root.'Services/Admin/ReservationAdminService.php',
        ] as $file) {
            $source = file_get_contents($file);
            self::assertIsString($source);
            self::assertStringContainsString('ReservationSettings', $source, $file);
            self::assertStringNotContainsString('ShopSettingRepository', $source, $file);
        }
    }

    public function test_notification_and_line_services_do_not_depend_on_the_broad_settings_contract(): void
    {
        $root = dirname(__DIR__, 3).'/app/';

        foreach ([
            $root.'Services/AnnouncementNotificationService.php',
            $root.'Services/CouponNotificationService.php',
            $root.'Services/OrderNotificationService.php',
            $root.'Services/Line/LineWebhookProjectResolver.php',
            $root.'Services/Line/LineIdTokenVerifier.php',
            $root.'UseCases/Line/LineWebhookHandleUseCase.php',
        ] as $file) {
            $source = file_get_contents($file);
            self::assertIsString($source);
            self::assertStringNotContainsString('ShopSettingRepository', $source, $file);
        }
    }

    public function test_stamp_use_cases_depend_on_their_focused_settings_contract(): void
    {
        $directory = dirname(__DIR__, 3).'/app/UseCases/Stamp';

        foreach (['StampGrantUseCase.php', 'StampGrantByCodeUseCase.php', 'StampIndexUseCase.php'] as $filename) {
            $file = $directory.'/'.$filename;
            $source = file_get_contents($file);
            self::assertIsString($source);
            self::assertStringContainsString('StampSettings', $source, $file);
            self::assertStringNotContainsString('ShopSettingRepository', $source, $file);
        }
    }

    public function test_wallet_consumers_depend_on_their_focused_settings_contract(): void
    {
        $root = dirname(__DIR__, 3).'/app/';

        foreach ([
            $root.'UseCases/Admin/Settings/GetWalletSettingsUseCase.php',
            $root.'UseCases/Admin/Settings/UpdateWalletSettingsUseCase.php',
            $root.'Services/GoogleWalletPassService.php',
        ] as $file) {
            $source = file_get_contents($file);
            self::assertIsString($source);
            self::assertStringContainsString('WalletSettings', $source, $file);
            self::assertStringNotContainsString('ShopSettingRepository', $source, $file);
        }
    }

    public function test_google_wallet_service_declares_its_collaborators(): void
    {
        $file = dirname(__DIR__, 3).'/app/Services/GoogleWalletPassService.php';
        $source = file_get_contents($file);

        self::assertIsString($source);
        self::assertStringContainsString('private readonly MemberDataReader $memberData', $source);
        self::assertStringContainsString(
            'private readonly MemberCardAppearanceService $memberCardAppearanceService',
            $source
        );
        self::assertStringNotContainsString('app(MemberDataReader::class)', $source);
        self::assertStringNotContainsString('app(MemberCardAppearanceService::class)', $source);
    }

    public function test_services_do_not_hide_collaborators_behind_the_container(): void
    {
        $root = dirname(__DIR__, 3).'/app/';
        // AdminBaseController の共通ヘルパーは、全サブクラスのアクションへの注入を避けるため例外とする
        $allowed = [$root.'Http/Controllers/Admin/AdminBaseController.php'];
        $violations = [];

        foreach (['Services', 'UseCases', 'Http/Controllers', 'Jobs'] as $directory) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.$directory)) as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php' || in_array($file->getPathname(), $allowed, true)) {
                    continue;
                }

                $source = file_get_contents($file->getPathname());
                self::assertIsString($source);
                if (preg_match('/\\b(?:app|resolve)\\s*\\(\\s*[A-Z][A-Za-z0-9_\\\\]*::class/', $source) === 1) {
                    $violations[] = $file->getPathname();
                }
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    public function test_admin_settings_and_store_app_middleware_do_not_depend_on_the_broad_settings_contract(): void
    {
        $root = dirname(__DIR__, 3).'/app/';
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root.'UseCases/Admin/Settings')
        );
        $targets = [
            $root.'Http/Middleware/AuthenticateStoreApp.php',
            $root.'Http/Middleware/AuthorizeQueueAccess.php',
        ];

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $targets[] = $file->getPathname();
            }
        }

        foreach ($targets as $file) {
            $source = file_get_contents($file);
            self::assertIsString($source);
            self::assertStringNotContainsString('ShopSettingRepository', $source, $file);
        }
    }

    public function test_member_rank_profile_and_queue_consumers_use_focused_settings_contracts(): void
    {
        $root = dirname(__DIR__, 3).'/app/';

        foreach ([
            'Services/Admin/MemberAdminListQueryService.php',
            'Services/IssueCouponOnRankUpService.php',
            'Services/MemberRankCalculator.php',
            'Services/MemberRankMetricCalculator.php',
            'Services/MemberRankSyncService.php',
            'Services/Smaregi/SmaregiCustomerRankAssignmentService.php',
            'UseCases/Admin/AfterRegistrationDelivery/AdminAfterRegistrationDeliveryCreateUseCase.php',
            'UseCases/Admin/AfterRegistrationDelivery/AdminAfterRegistrationDeliveryEditUseCase.php',
            'UseCases/Admin/Coupon/AdminCouponBulkIssueFormUseCase.php',
            'UseCases/Admin/Coupon/AdminCouponDeliveryDetailUseCase.php',
            'UseCases/Admin/Member/AdminMemberEditUseCase.php',
            'UseCases/Admin/Member/AdminMemberUpdateUseCase.php',
            'UseCases/Admin/Member/GetAdminMemberListExtrasUseCase.php',
            'UseCases/Coupon/CouponMyCouponsUseCase.php',
            'UseCases/Member/MemberPointCardUseCase.php',
            'UseCases/Queue/QueueQrStatusUseCase.php',
            'Support/QueueLiffUrlBuilder.php',
            'Http/Requests/Admin/MemberUpdateRequest.php',
        ] as $relativePath) {
            $file = $root.$relativePath;
            $source = file_get_contents($file);
            self::assertIsString($source);
            self::assertStringNotContainsString('ShopSettingRepository', $source, $file);
        }
    }

    public function test_application_consumers_do_not_depend_on_the_broad_settings_contract(): void
    {
        $root = dirname(__DIR__, 3).'/app';
        $allowed = [
            $root.'/Infrastructure/Persistence/Eloquent/Settings/EloquentShopSettingRepository.php',
            $root.'/Providers/AppServiceProvider.php',
        ];
        $violations = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php' || in_array($file->getPathname(), $allowed, true)) {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            if (is_string($source) && str_contains($source, 'ShopSettingRepository')) {
                $violations[] = $file->getPathname();
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    /**
     * @param  list<string>  $forbiddenNamespaces
     */
    #[DataProvider('forbiddenDependencies')]
    public function test_layer_has_no_forbidden_dependencies(string $directory, array $forbiddenNamespaces): void
    {
        $violations = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            foreach ($forbiddenNamespaces as $namespace) {
                if (str_contains($source, 'use '.$namespace)) {
                    $violations[] = $file->getPathname().' imports '.$namespace;
                }
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }
}
