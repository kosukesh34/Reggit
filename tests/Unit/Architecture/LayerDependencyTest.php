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
