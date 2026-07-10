@php
    $pageKey = $pageKey ?? 'default';
    $guides = [
        'basic' => [
            'title' => 'この画面で変更される内容',
            'changes' => ['営業時間', '注文モード', '利用機能のON/OFF'],
            'notes' => ['予約時間帯・休業日・予約枠は「予約設定」で管理します。'],
            'links' => [
                ['label' => '予約設定', 'url' => admin_route('admin.settings.reservations'), 'permission' => 'reservations.view'],
                ['label' => '順番待ち設定', 'url' => admin_route('admin.settings.queue'), 'permission' => 'queue.view'],
            ],
        ],
        'home' => [
            'title' => 'ホーム設定の役割',
            'changes' => ['LIFFで表示するタブのON/OFF', '並び順（ドラッグ）', 'ラベル・アイコン'],
            'notes' => ['タブに表示される中身（UI部品）は「LIFFデザイン」で設定します。'],
            'links' => [
                ['label' => 'LIFFデザイン', 'url' => admin_route('admin.settings.liff-designer'), 'permission' => 'liff_designer.manage'],
            ],
        ],
        'reservations' => [
            'title' => '予約設定の役割',
            'changes' => ['予約時間帯', '休業日（曜日・日付）', '予約上限', '予約受付日数'],
            'notes' => ['営業時間の開始/終了は「基本設定」で変更します。'],
            'links' => [
                ['label' => '基本設定', 'url' => admin_route('admin.settings.basic'), 'permission' => 'settings.view'],
            ],
        ],
        'queue' => [
            'title' => '順番待ち設定の役割',
            'changes' => ['1組あたり平均対応時間（待ち時間計算用）'],
            'notes' => ['順番待ち機能自体の利用可否は「基本設定 > 利用機能」で切り替えます。'],
            'links' => [
                ['label' => '基本設定', 'url' => admin_route('admin.settings.basic'), 'permission' => 'settings.view'],
            ],
        ],
        'features' => [
            'title' => '機能設定の役割',
            'changes' => ['管理画面とLIFFの機能ON/OFF', '管理画面の配色'],
            'notes' => ['機能ごとの詳細設定は各機能メニュー内の「〜設定」で行います。'],
            'links' => [
                ['label' => '基本設定', 'url' => admin_route('admin.settings.basic'), 'permission' => 'settings.view'],
                ['label' => '詳細設定（配色）', 'url' => admin_route('admin.settings.advanced'), 'permission' => 'settings.view'],
            ],
        ],
        'advanced' => [
            'title' => '詳細設定（配色）の役割',
            'changes' => ['LINE画面の配色（メイン/成功/注意色）'],
            'notes' => ['タブの並びや表示内容は「ホーム設定」「LIFFデザイン」で調整します。'],
            'links' => [
                ['label' => 'ホーム設定', 'url' => admin_route('admin.settings.home'), 'permission' => 'settings.view'],
                ['label' => 'LIFFデザイン', 'url' => admin_route('admin.settings.liff-designer'), 'permission' => 'liff_designer.manage'],
            ],
        ],
        'coupons' => [
            'title' => 'クーポン設定の役割',
            'changes' => ['クーポン機能ON/OFF', '画像未設定時のデフォルト画像URL'],
            'notes' => ['クーポンの作成・配布は「クーポン一覧 / 一括配布」で行います。'],
            'links' => [
                ['label' => 'クーポン一覧', 'url' => admin_route('admin.coupons'), 'permission' => 'coupons.view'],
                ['label' => '一括配布', 'url' => admin_route('admin.coupons.bulk-issue'), 'permission' => 'coupons.manage'],
            ],
        ],
        'announcements' => [
            'title' => 'お知らせ設定の役割',
            'changes' => ['お知らせ機能ON/OFF', '新規作成時のデフォルト挙動'],
            'notes' => ['実際の投稿と配信は「お知らせ一覧 / 配信」から行います。'],
            'links' => [
                ['label' => 'お知らせ一覧', 'url' => admin_route('admin.announcements'), 'permission' => 'announcements.view'],
                ['label' => '配信', 'url' => admin_route('admin.announcements.delivery.create'), 'permission' => 'announcements.manage'],
            ],
        ],
        'gallery' => [
            'title' => 'ギャラリー設定の役割',
            'changes' => ['ギャラリー機能ON/OFF'],
            'notes' => ['カテゴリ・画像の登録は「ギャラリー」画面で行います。'],
            'links' => [
                ['label' => 'ギャラリー', 'url' => admin_route('admin.gallery.categories'), 'permission' => 'gallery.view'],
            ],
        ],
        'profile' => [
            'title' => 'プロフィール設定の役割',
            'changes' => ['会員登録/編集で表示する項目', '必須/任意の切り替え'],
            'notes' => ['会員の実データ編集は「会員管理」から行います。'],
            'links' => [
                ['label' => '会員管理', 'url' => admin_route('admin.members'), 'permission' => 'members.view'],
            ],
        ],
        'wallet' => [
            'title' => 'Wallet画像設定の役割',
            'changes' => ['Apple Wallet/Google Walletに表示する画像'],
            'notes' => ['画像未設定時はデフォルト設定または .env の値が使用されます。'],
            'links' => [
                ['label' => '会員管理', 'url' => admin_route('admin.members'), 'permission' => 'members.view'],
            ],
        ],
        'ranks' => [
            'title' => 'ランク・ポイント設定の役割',
            'changes' => ['ランク判定基準', '会員証表示', 'ポイント付与関連設定'],
            'notes' => ['会員への個別付与・調整は会員詳細画面で行います。'],
            'links' => [
                ['label' => '会員管理', 'url' => admin_route('admin.members'), 'permission' => 'members.view'],
            ],
        ],
        'smaregi' => [
            'title' => 'スマレジ連携設定の役割',
            'changes' => ['契約IDの紐づけ', '商品同期の実行'],
            'notes' => ['Client ID/Secret は .env で管理し、管理画面からは変更しません。'],
            'links' => [],
        ],
    ];
    $guide = $guides[$pageKey] ?? null;
@endphp

@if($guide)
<div class="card settings-context-card">
    <div class="card-body">
        <h3 class="settings-context-title"><i class="fas fa-circle-info"></i> {{ $guide['title'] }}</h3>
        <div class="settings-context-grid">
            <div>
                <div class="settings-context-label">変更される項目</div>
                <ul class="settings-context-list">
                    @foreach($guide['changes'] as $item)
                    <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            <div>
                <div class="settings-context-label">補足</div>
                <ul class="settings-context-list">
                    @foreach($guide['notes'] as $item)
                    <li>{{ $item }}</li>
                    @endforeach
                </ul>
                @if(!empty($guide['links']))
                <div class="settings-context-links">
                    @foreach($guide['links'] as $link)
                    @php
                        $perm = $link['permission'] ?? null;
                        $canShow = !auth('admin')->check()
                            || $perm === null
                            || auth('admin')->user()->hasPermission($perm);
                    @endphp
                    @if($canShow)
                    <a href="{{ $link['url'] }}" class="btn btn-sm btn-outline-secondary">{{ $link['label'] }}</a>
                    @endif
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
