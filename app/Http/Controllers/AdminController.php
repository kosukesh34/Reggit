<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\AnnouncementStoreRequest;
use App\Http\Requests\Admin\AnnouncementUpdateRequest;
use App\Http\Requests\Admin\CouponStoreRequest;
use App\Http\Requests\Admin\CouponUpdateRequest;
use App\Http\Requests\Admin\OrderStatusRequest;
use App\Http\Requests\Admin\ProductStoreRequest;
use App\Http\Requests\Admin\ProductUpdateRequest;
use App\Http\Requests\Admin\QueueStatusRequest;
use App\Http\Requests\Admin\ReservationStatusRequest;
use App\Http\Requests\Admin\SettingsAdvancedRequest;
use App\Http\Requests\Admin\SettingsBasicRequest;
use App\Http\Requests\Admin\SettingsMenuRequest;
use App\Http\Requests\Admin\StampStoreRequest;
use App\Http\Requests\Admin\StampUpdateRequest;
use App\Models\AdminUser;
use App\Models\CouponRedemption;
use App\Models\GalleryCategory;
use App\Models\GalleryItem;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\QueueEntry;
use App\Models\Reservation;
use App\Models\ShopSetting;
use App\Services\Admin\AnnouncementAdminService;
use App\Services\Admin\CouponAdminService;
use App\Services\Admin\DashboardService;
use App\Services\Admin\MemberAdminService;
use App\Services\Admin\OrderAdminService;
use App\Services\Admin\ProductAdminService;
use App\Services\Admin\QueueAdminService;
use App\Services\Admin\ReservationAdminService;
use App\Services\Admin\StampAdminService;
use App\Services\SettingsService;
use App\Util\AdminContext;
use App\Util\AdminStatusLabels;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * プレゼンテーション層: 店舗管理画面の入出力のみ。業務処理は Services（アプリケーション層）に委譲。
 */
class AdminController extends Controller
{
    public function __construct(
        private SettingsService $settingsService,
        private DashboardService $dashboardService,
        private ProductAdminService $productAdminService,
        private OrderAdminService $orderAdminService,
        private MemberAdminService $memberAdminService,
        private ReservationAdminService $reservationAdminService,
        private QueueAdminService $queueAdminService,
        private StampAdminService $stampAdminService,
        private CouponAdminService $couponAdminService,
        private AnnouncementAdminService $announcementAdminService,
    ) {
    }

    /** 自作: 現在の管理コンテキストからプロジェクトIDを解決する */
    protected function resolveAdminProjectId(): int
    {
        $id = AdminContext::getCurrentProjectId();
        if ($id !== null) {
            return $id;
        }
        $admin = auth('admin')->user();
        return AdminUser::resolveCurrentProjectId(
            $admin,
            (int) session('admin_project_id'),
            request()->input('project_id') ? (int) request()->input('project_id') : null
        );
    }

    /** 自作: 現在の管理コンテキストからプロジェクトキーを解決する */
    protected function resolveAdminProjectKey(): string
    {
        $project = AdminContext::getCurrentProject();
        return $project ? $project->project_key : 'default';
    }

    /** 自作: 管理画面ルート用のパラメータ配列を組み立てる */
    protected function buildAdminRouteParams(): array
    {
        return ['project_key' => $this->resolveAdminProjectKey()];
    }

    // --- ダッシュボード ---
    public function dashboard()
    {
        $stats = $this->dashboardService->getStats();
        $recent_orders = $this->dashboardService->getRecentOrders(10);
        return view('admin.dashboard', compact('stats', 'recent_orders'));
    }

    // --- 商品 ---
    public function products()
    {
        $products = $this->productAdminService->getPaginatedList(20);
        return view('admin.products', compact('products'));
    }

    public function createProduct()
    {
        return view('admin.product-form');
    }

    public function storeProduct(ProductStoreRequest $request)
    {
        $this->productAdminService->create($this->resolveAdminProjectId(), $request->validated());
        return redirect()->route('admin.products', $this->buildAdminRouteParams())
            ->with('success', '商品を追加しました');
    }

    public function editProduct($id)
    {
        $product = $this->productAdminService->findOrFail((int) $id);
        return view('admin.product-form', compact('product'));
    }

    public function updateProduct(ProductUpdateRequest $request, $id)
    {
        $this->productAdminService->update((int) $id, $request->validated());
        return redirect()->route('admin.products', $this->buildAdminRouteParams())
            ->with('success', '商品を更新しました');
    }

    public function deleteProduct($id)
    {
        $this->productAdminService->delete((int) $id);
        return redirect()->route('admin.products', $this->buildAdminRouteParams())
            ->with('success', '商品を削除しました');
    }

    // --- 注文 ---
    public function orders(Request $request)
    {
        $orders = $this->orderAdminService->getPaginatedList($request->query('status'), 20);
        $orders->appends($request->query());
        $statusCounts = $this->orderAdminService->getStatusCounts();
        return view('admin.orders', compact('orders', 'statusCounts'));
    }

    public function orderDetail($id)
    {
        $order = $this->orderAdminService->findOrFail((int) $id);
        return view('admin.order-detail', compact('order'));
    }

    public function updateOrderStatus(OrderStatusRequest $request, $id)
    {
        $status = $request->validated('status');
        $this->orderAdminService->updateStatus((int) $id, $status);
        return redirect()->back()
            ->with('success', '注文ステータスを「' . AdminStatusLabels::orderStatusLabel($status) . '」に更新しました');
    }

    public function completeOrder($id)
    {
        $this->orderAdminService->complete((int) $id);
        return redirect()->back()->with('success', '注文を完了しました');
    }

    // --- 会員 ---
    public function members()
    {
        $members = $this->memberAdminService->getPaginatedList(20);
        return view('admin.members', compact('members'));
    }

    public function memberDetail($id)
    {
        $member = $this->memberAdminService->findOrFail((int) $id);
        return view('admin.member-detail', compact('member'));
    }

    // --- 設定 ---
    public function settingsBasic()
    {
        $settings = $this->settingsService->getBasicSettings($this->resolveAdminProjectId());
        return view('admin.settings.basic', ['settings' => $settings]);
    }

    public function updateSettingsBasic(SettingsBasicRequest $request)
    {
        $this->settingsService->updateReservationSettings($request->validated(), $this->resolveAdminProjectId());
        return redirect()->route('admin.settings.basic', $this->buildAdminRouteParams())
            ->with('success', '基本設定を更新しました');
    }

    public function settingsAdvanced()
    {
        $settings = $this->settingsService->getAdvancedSettings($this->resolveAdminProjectId());
        return view('admin.settings.advanced', ['settings' => $settings]);
    }

    public function updateSettingsAdvanced(SettingsAdvancedRequest $request)
    {
        $this->settingsService->updateLineThemeSettings($request->validated(), $this->resolveAdminProjectId());
        return redirect()->route('admin.settings.advanced', $this->buildAdminRouteParams())
            ->with('success', '配色設定を更新しました');
    }

    public function settingsMenu()
    {
        $settings = $this->settingsService->getMenuSettings($this->resolveAdminProjectId());
        return view('admin.settings.menu', ['settings' => $settings]);
    }

    public function updateSettingsMenu(SettingsMenuRequest $request)
    {
        $this->settingsService->updateMenuSettings($request->validated(), $this->resolveAdminProjectId());
        return redirect()->route('admin.settings.menu', $this->buildAdminRouteParams())
            ->with('success', 'メニュー設定を更新しました');
    }

    public function settingsRanks()
    {
        $projectId = $this->resolveAdminProjectId();
        $rankSettings = ShopSetting::getMemberRankSettings($projectId);
        $rankBasis = ShopSetting::getRankBasis($projectId);
        $pointEarnRate = ShopSetting::getPointEarnRate($projectId);
        $pointsValidDays = ShopSetting::getPointsValidDays($projectId);

        return view('admin.settings.ranks', compact(
            'rankSettings',
            'rankBasis',
            'pointEarnRate',
            'pointsValidDays'
        ));
    }

    public function updateSettingsRanks(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();

        $validated = $request->validate([
            'rank_basis' => 'required|in:points,stamps,max',
            'point_earn_rate' => 'required|integer|min:1|max:100000',
            'points_valid_days' => 'required|integer|min:1|max:3650',
            'ranks' => 'array',
            'ranks.*.key' => 'nullable|string|max:50',
            'ranks.*.label' => 'nullable|string|max:50',
            'ranks.*.min_points' => 'nullable|integer|min:0|max:10000000',
            'ranks.*.order' => 'nullable|integer|min:0|max:999',
            'ranks.*.is_active' => 'nullable',
            'ranks.*.icon_url' => 'nullable|url|max:500',
            'ranks.*.stamp_background_url' => 'nullable|url|max:500',
        ]);

        $rows = collect($validated['ranks'] ?? [])
            ->filter(fn ($row) => !empty($row['key']))
            ->map(function ($row, $index) {
                return [
                    'key' => (string) $row['key'],
                    'label' => (string) ($row['label'] ?? $row['key']),
                    'min_points' => (int) ($row['min_points'] ?? 0),
                    'is_active' => !empty($row['is_active']),
                    'icon_url' => $row['icon_url'] ?? null,
                    'stamp_background_url' => $row['stamp_background_url'] ?? null,
                    'order' => (int) ($row['order'] ?? ($index + 1)),
                ];
            })
            ->values()
            ->all();

        $keys = array_map(fn ($r) => $r['key'], $rows);
        if (count($keys) !== count(array_unique($keys))) {
            return redirect()->back()->withInput()->withErrors(['ranks' => 'ランクキーが重複しています']);
        }

        ShopSetting::setMemberRankSettings($rows, $projectId);
        ShopSetting::setValue('member_rank_basis', $validated['rank_basis'], 'string', $projectId);
        ShopSetting::setValue('point_earn_rate', $validated['point_earn_rate'], 'integer', $projectId);
        ShopSetting::setValue('points_valid_days', $validated['points_valid_days'], 'integer', $projectId);

        return redirect()->route('admin.settings.ranks', $this->buildAdminRouteParams())
            ->with('success', 'ランク・ポイント設定を更新しました');
    }

    public function settingsFeatures()
    {
        $projectId = $this->resolveAdminProjectId();
        $featureFlags = ShopSetting::getFeatureFlags($projectId);
        $featureFlagLabels = ShopSetting::FEATURE_FLAG_LABELS;
        $adminSidebarColor = ShopSetting::getAdminSidebarColor($projectId);
        $adminHeaderColor = ShopSetting::getAdminHeaderColor($projectId);
        $adminPrimaryColor = ShopSetting::getAdminPrimaryColor($projectId);
        $stampDefaultImageUrl = ShopSetting::getStampDefaultImageUrl($projectId);
        $couponDefaultImageUrl = ShopSetting::getCouponDefaultImageUrl($projectId);
        $frontendLogoUrl = ShopSetting::getFrontendLogoUrl($projectId);
        $frontendHeaderText = ShopSetting::getFrontendHeaderText($projectId);

        return view('admin.settings.features', compact(
            'featureFlags', 'featureFlagLabels',
            'adminSidebarColor', 'adminHeaderColor', 'adminPrimaryColor',
            'stampDefaultImageUrl', 'couponDefaultImageUrl',
            'frontendLogoUrl', 'frontendHeaderText'
        ));
    }

    public function updateSettingsFeatures(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();

        // 機能フラグ更新
        ShopSetting::setFeatureFlags($request->input('features', []), $projectId);

        // 管理画面 UI カスタマイズ
        ShopSetting::setValue('admin_sidebar_color', $request->input('admin_sidebar_color', ''), 'string', $projectId);
        ShopSetting::setValue('admin_header_color', $request->input('admin_header_color', ''), 'string', $projectId);
        ShopSetting::setValue('admin_primary_color', $request->input('admin_primary_color', ''), 'string', $projectId);
        ShopSetting::setValue('stamp_default_image_url', $request->input('stamp_default_image_url', ''), 'string', $projectId);
        ShopSetting::setValue('coupon_default_image_url', $request->input('coupon_default_image_url', ''), 'string', $projectId);
        ShopSetting::setValue('frontend_logo_url', $request->input('frontend_logo_url', ''), 'string', $projectId);
        ShopSetting::setValue('frontend_header_text', $request->input('frontend_header_text', ''), 'string', $projectId);

        return redirect()->route('admin.settings.features', $this->buildAdminRouteParams())
            ->with('success', '機能・カスタマイズ設定を更新しました');
    }

    public function settingsLiffDesigner()
    {
        $projectId = $this->resolveAdminProjectId();
        $layouts = ShopSetting::getLiffTabLayouts($projectId);
        $templates = ShopSetting::defaultLiffDesignerTemplates();
        return view('admin.settings.liff-designer', compact('layouts', 'templates'));
    }

    public function updateSettingsLiffDesigner(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $layoutsJson = $request->input('layouts_json');
        $decoded = json_decode($layoutsJson, true);
        if (!is_array($decoded)) {
            return redirect()->back()->withErrors(['layouts' => 'レイアウトが不正です']);
        }
        ShopSetting::setLiffTabLayouts($decoded, $projectId);
        return redirect()->route('admin.settings.liff-designer', $this->buildAdminRouteParams())
            ->with('success', 'LIFFデザインを更新しました');
    }

    public function galleryCategories()
    {
        $categories = GalleryCategory::orderBy('sort_order')->orderBy('id')->get();
        return view('admin.gallery.categories', compact('categories'));
    }

    public function storeGalleryCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'is_active' => 'nullable',
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['project_id'] = $this->resolveAdminProjectId();
        GalleryCategory::create($validated);
        return redirect()->route('admin.gallery.categories', $this->buildAdminRouteParams())
            ->with('success', 'カテゴリを追加しました');
    }

    public function updateGalleryCategory(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'is_active' => 'nullable',
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $category = GalleryCategory::findOrFail($id);
        $category->update($validated);
        return redirect()->route('admin.gallery.categories', $this->buildAdminRouteParams())
            ->with('success', 'カテゴリを更新しました');
    }

    public function deleteGalleryCategory(int $id)
    {
        $category = GalleryCategory::findOrFail($id);
        $category->delete();
        return redirect()->route('admin.gallery.categories', $this->buildAdminRouteParams())
            ->with('success', 'カテゴリを削除しました');
    }

    public function galleryItems(Request $request)
    {
        $categories = GalleryCategory::orderBy('sort_order')->orderBy('id')->get();
        $query = GalleryItem::with('category')->orderBy('sort_order')->orderBy('id');
        if ($request->query('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }
        $items = $query->get();
        return view('admin.gallery.items', compact('items', 'categories'));
    }

    public function storeGalleryItem(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:gallery_categories,id',
            'title' => 'nullable|string|max:150',
            'image_url' => 'required|url|max:500',
            'description' => 'nullable|string|max:1000',
            'size' => 'nullable|in:small,medium,large',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'is_active' => 'nullable',
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['project_id'] = $this->resolveAdminProjectId();
        GalleryItem::create($validated);
        return redirect()->route('admin.gallery.items', $this->buildAdminRouteParams())
            ->with('success', '画像を追加しました');
    }

    public function updateGalleryItem(Request $request, int $id)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:gallery_categories,id',
            'title' => 'nullable|string|max:150',
            'image_url' => 'required|url|max:500',
            'description' => 'nullable|string|max:1000',
            'size' => 'nullable|in:small,medium,large',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'is_active' => 'nullable',
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $item = GalleryItem::findOrFail($id);
        $item->update($validated);
        return redirect()->route('admin.gallery.items', $this->buildAdminRouteParams())
            ->with('success', '画像を更新しました');
    }

    public function deleteGalleryItem(int $id)
    {
        $item = GalleryItem::findOrFail($id);
        $item->delete();
        return redirect()->route('admin.gallery.items', $this->buildAdminRouteParams())
            ->with('success', '画像を削除しました');
    }

    public function analytics(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $start = $request->query('start', now()->subDays(29)->toDateString());
        $end = $request->query('end', now()->toDateString());

        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->endOfDay();

        $ordersBase = Order::whereBetween('created_at', [$startDate, $endDate]);
        $ordersCount = (clone $ordersBase)->count();
        $revenue = (clone $ordersBase)->where('status', '!=', 'cancelled')->sum('total_amount');
        $avgOrderValue = $ordersCount > 0 ? ($revenue / $ordersCount) : 0;

        $newMembers = Member::whereBetween('created_at', [$startDate, $endDate])->count();
        $reservationsCount = Reservation::whereBetween('reserved_at', [$startDate, $endDate])->count();
        $queueCount = QueueEntry::whereBetween('created_at', [$startDate, $endDate])->count();
        $couponsIssued = MemberCoupon::whereBetween('issued_at', [$startDate, $endDate])->count();
        $couponsUsed = CouponRedemption::whereBetween('used_at', [$startDate, $endDate])->count();
        $pointsEarned = PointTransaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('type', 'earned')
            ->sum('points');
        $pointsUsed = abs(
            PointTransaction::whereBetween('created_at', [$startDate, $endDate])
                ->where('type', 'used')
                ->sum('points')
        );

        $period = CarbonPeriod::create($startDate, $endDate);
        $labels = [];
        $ordersByDate = [];
        $revenueByDate = [];
        $membersByDate = [];
        foreach ($period as $date) {
            $label = $date->format('Y-m-d');
            $labels[] = $label;
            $ordersByDate[$label] = 0;
            $revenueByDate[$label] = 0;
            $membersByDate[$label] = 0;
        }

        $orderStats = (clone $ordersBase)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'), DB::raw('SUM(CASE WHEN status != \"cancelled\" THEN total_amount ELSE 0 END) as revenue'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get();

        foreach ($orderStats as $row) {
            $key = $row->date;
            if (array_key_exists($key, $ordersByDate)) {
                $ordersByDate[$key] = (int) $row->count;
                $revenueByDate[$key] = (float) $row->revenue;
            }
        }

        $memberStats = Member::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get();

        foreach ($memberStats as $row) {
            $key = $row->date;
            if (array_key_exists($key, $membersByDate)) {
                $membersByDate[$key] = (int) $row->count;
            }
        }

        return view('admin.analytics', [
            'start' => $startDate->toDateString(),
            'end' => $endDate->toDateString(),
            'summary' => [
                'orders' => $ordersCount,
                'revenue' => $revenue,
                'avg_order_value' => $avgOrderValue,
                'new_members' => $newMembers,
                'reservations' => $reservationsCount,
                'queue' => $queueCount,
                'coupons_issued' => $couponsIssued,
                'coupons_used' => $couponsUsed,
                'points_earned' => $pointsEarned,
                'points_used' => $pointsUsed,
            ],
            'chart' => [
                'labels' => $labels,
                'orders' => array_values($ordersByDate),
                'revenue' => array_values($revenueByDate),
                'members' => array_values($membersByDate),
            ],
        ]);
    }

    // --- スタンプ ---
    public function stamps()
    {
        $stampCards = $this->stampAdminService->getPaginatedList(20);
        return view('admin.stamps.index', compact('stampCards'));
    }

    public function createStamp()
    {
        return view('admin.stamps.create');
    }

    public function storeStamp(StampStoreRequest $request)
    {
        $this->stampAdminService->create($this->resolveAdminProjectId(), $request->validated());
        return redirect()->route('admin.stamps', $this->buildAdminRouteParams())
            ->with('success', 'スタンプカードを追加しました');
    }

    public function editStamp(int $id)
    {
        $stampCard = $this->stampAdminService->findOrFail($id);
        return view('admin.stamps.edit', compact('stampCard'));
    }

    public function updateStamp(StampUpdateRequest $request, int $id)
    {
        $this->stampAdminService->update($id, $request->validated());
        return redirect()->route('admin.stamps', $this->buildAdminRouteParams())
            ->with('success', 'スタンプカードを更新しました');
    }

    public function deleteStamp(int $id)
    {
        $this->stampAdminService->delete($id);
        return redirect()->route('admin.stamps', $this->buildAdminRouteParams())
            ->with('success', 'スタンプカードを削除しました');
    }

    // --- クーポン ---
    public function coupons()
    {
        $coupons = $this->couponAdminService->getPaginatedList(20);
        return view('admin.coupons.index', compact('coupons'));
    }

    public function createCoupon()
    {
        $stampCards = $this->couponAdminService->getActiveStampCardsForSelect();
        return view('admin.coupons.create', compact('stampCards'));
    }

    public function storeCoupon(CouponStoreRequest $request)
    {
        $this->couponAdminService->create($this->resolveAdminProjectId(), $request->validated());
        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポンを作成しました');
    }

    public function editCoupon(int $id)
    {
        $coupon = $this->couponAdminService->findOrFail($id);
        $stampCards = $this->couponAdminService->getActiveStampCardsForSelect();
        return view('admin.coupons.edit', compact('coupon', 'stampCards'));
    }

    public function updateCoupon(CouponUpdateRequest $request, int $id)
    {
        $this->couponAdminService->update($id, $request->validated());
        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポンを更新しました');
    }

    public function deleteCoupon(int $id)
    {
        $this->couponAdminService->delete($id);
        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポンを削除しました');
    }

    // --- お知らせ ---
    public function announcements()
    {
        $announcements = $this->announcementAdminService->getPaginatedList(20);
        return view('admin.announcements.index', compact('announcements'));
    }

    public function createAnnouncement()
    {
        return view('admin.announcements.create');
    }

    public function storeAnnouncement(AnnouncementStoreRequest $request)
    {
        $this->announcementAdminService->create($this->resolveAdminProjectId(), $request->validated());
        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', 'お知らせを追加しました');
    }

    public function editAnnouncement(int $id)
    {
        $announcement = $this->announcementAdminService->findOrFail($id);
        return view('admin.announcements.edit', compact('announcement'));
    }

    public function updateAnnouncement(AnnouncementUpdateRequest $request, int $id)
    {
        $this->announcementAdminService->update($id, $request->validated());
        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', 'お知らせを更新しました');
    }

    public function deleteAnnouncement(int $id)
    {
        $this->announcementAdminService->delete($id);
        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', 'お知らせを削除しました');
    }

    // --- 順番待ち ---
    public function queue()
    {
        $projectId = $this->resolveAdminProjectId();
        $entries = $this->queueAdminService->getPaginatedList(50);
        $waitingCount = $this->queueAdminService->getWaitingCount();
        $estimatedWaitMinutes = $this->queueAdminService->getEstimatedWaitMinutes($projectId);
        $waitingQueueNumbers = $this->queueAdminService->getWaitingQueueNumbers();
        return view('admin.queue.index', compact('entries', 'waitingCount', 'estimatedWaitMinutes', 'waitingQueueNumbers'));
    }

    public function updateQueueStatus(QueueStatusRequest $request, int $id)
    {
        $status = $request->validated('status');
        $this->queueAdminService->updateStatus($id, $status);
        return redirect()->back()
            ->with('success', 'ステータスを「' . AdminStatusLabels::queueStatusLabel($status) . '」に更新しました');
    }

    // --- 予約 ---
    public function reservations(Request $request)
    {
        $payload = $this->reservationAdminService->getReservationIndexPayload(
            $this->resolveAdminProjectId(),
            $request->query('status'),
            $request->query('date'),
            $request->query()
        );
        return view('admin.reservations', $payload);
    }

    public function reservationDetail($id)
    {
        $reservation = $this->reservationAdminService->findOrFail((int) $id);
        return view('admin.reservation-detail', compact('reservation'));
    }

    public function updateReservationStatus(ReservationStatusRequest $request, $id)
    {
        $status = $request->validated('status');
        $this->reservationAdminService->updateStatus((int) $id, $status);
        return redirect()->back()
            ->with('success', '予約ステータスを「' . AdminStatusLabels::reservationStatusLabel($status) . '」に更新しました');
    }

    public function completeReservation($id)
    {
        $this->reservationAdminService->complete((int) $id);
        return redirect()->back()->with('success', '予約を完了しました');
    }
}
