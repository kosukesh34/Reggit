<?php

namespace Tests\Unit;

use App\Support\WebNavigation;
use Illuminate\Http\Request;
use Tests\TestCase;

class WebNavigationTest extends TestCase
{
    public function test_direct_links_return_to_the_correct_parent_in_the_same_project(): void
    {
        $cases = [
            ['/admin/shop-a/orders/7', '/admin/shop-a/orders', '注文一覧へ戻る'],
            ['/admin/shop-a/members/7/edit', '/admin/shop-a/members/7', '会員詳細へ戻る'],
            ['/admin/shop-a/members/7/visits/9', '/admin/shop-a/members/7', '会員詳細へ戻る'],
            ['/admin/shop-a/reservations/7/edit', '/admin/shop-a/reservations/7', '予約詳細へ戻る'],
            ['/admin/shop-a/settings/basic', '/admin/shop-a/settings', '設定一覧へ戻る'],
            ['/admin/shop-a/gallery/categories', '/admin/shop-a/gallery/items', '画像一覧へ戻る'],
            ['/admin/shop-a/coupons/deliveries/7', '/admin/shop-a/coupons/bulk-issue', 'クーポン配布へ戻る'],
            ['/admin/shop-a/help/member-registration', '/admin/shop-a/help', 'ヘルプ一覧へ戻る'],
            ['/system-admin/help-articles/7/edit', '/system-admin/help-articles', 'ヘルプ一覧へ戻る'],
            ['/system-admin/accounts/create', '/system-admin/accounts', 'アカウント一覧へ戻る'],
        ];
        foreach ($cases as [$path, $parent, $label]) {
            $back = $this->backFor($path);
            $this->assertSame(url($parent), $back['href']);
            $this->assertSame($label, $back['label']);
        }
        $this->assertNull($this->backFor('/admin/shop-a/dashboard'));
        $this->assertNull($this->backFor('/system-admin/dashboard'));
    }

    public function test_all_admin_get_pages_have_a_parent_route_without_a_self_link(): void
    {
        foreach (app('router')->getRoutes() as $route) {
            $name = $route->getName() ?? '';
            if (! in_array('GET', $route->methods(), true)
                || ! in_array('auth:admin', $route->middleware(), true)
                || ! preg_match('/^(admin|system-admin)\./', $name)
                || str_starts_with($name, 'admin.password.')) {
                continue;
            }
            $params = array_fill_keys($route->parameterNames(), 7);
            if (isset($params['project_key'])) {
                $params['project_key'] = 'shop-a';
            }
            $request = Request::create(route($name, $params));
            $boundRoute = clone $route;
            $boundRoute->bind($request);
            $request->setRouteResolver(fn () => $boundRoute);
            $back = WebNavigation::adminBack($request);
            if ($back !== null) {
                $this->assertNotSame($request->url(), $back['href'], $name);
                $parent = app('router')->getRoutes()->match(Request::create($back['href']));
                $this->assertNotNull($parent->getName(), $name);
            }
        }
    }

    /** @return array{href: string, label: string}|null */
    private function backFor(string $path): ?array
    {
        $request = Request::create($path);
        $route = app('router')->getRoutes()->match($request);
        $request->setRouteResolver(fn () => $route);

        return WebNavigation::adminBack($request);
    }
}
