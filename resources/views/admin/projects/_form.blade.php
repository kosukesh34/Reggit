<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-layer-group"></i> 基本情報</h3>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="project_key" class="form-label">プロジェクトキー <span class="text-danger">*</span></label>
            <input type="text" name="project_key" id="project_key" class="form-control" value="{{ old('project_key', optional($view->project)->project_key ?? '') }}" required maxlength="64" pattern="[a-zA-Z0-9_-]+">
            <p class="form-text text-muted">URLやAPIで利用する識別子です（英数字・ハイフン・アンダースコア）。</p>
            @error('project_key')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="name" class="form-label">プロジェクト名 <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', optional($view->project)->name ?? '') }}" required maxlength="255">
            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="description" class="form-label">説明</label>
            <input type="text" name="description" id="description" class="form-control" value="{{ old('description', optional($view->project)->description ?? '') }}" maxlength="255">
            @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="custom_shop_domain" class="form-label">ショップ独自ドメイン</label>
            <input type="text" name="custom_shop_domain" id="custom_shop_domain" class="form-control" value="{{ old('custom_shop_domain', optional($view->project)->custom_shop_domain ?? '') }}" maxlength="255" placeholder="shop.example.com">
            <div class="form-text">PC向け公開ショップ専用。CNAME を Reggit に向けてください（LIFF / 管理画面は既存ドメインのまま）。</div>
            @error('custom_shop_domain')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="plan" class="form-label">プラン <span class="text-danger">*</span></label>
            <select name="plan" id="plan" class="form-control" required>
                @foreach(\App\Enums\ProjectPlan::getValues() as $value => $label)
                    <option value="{{ $value }}" {{ old('plan', optional($view->project)->plan ?? 'normal') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('plan')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="line_plan_enabled" value="1" @checked((bool) old('line_plan_enabled', $view->project?->line_plan_enabled ?? false))>
                <span>LINEプラン</span>
            </label>
            <p class="form-text text-muted">ONにすると、お知らせ配信画面に今月のLINEメッセージ送信数を表示します。</p>
            @error('line_plan_enabled')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="max_active_stores" class="form-label">利用可能な店舗数 <span class="text-danger">*</span></label>
            <input type="number" name="max_active_stores" id="max_active_stores" class="form-control" value="{{ old('max_active_stores', $view->project?->max_active_stores ?? 1) }}" required min="1" max="1000" step="1">
            @error('max_active_stores')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="service_status">利用状態</label>
            <select id="service_status" name="service_status" class="form-control">
                @foreach(['active'=>'利用中', 'unpaid'=>'未払い・利用停止', 'cancelled'=>'解約'] as $value=>$label)
                <option value="{{ $value }}" @selected(old('service_status', $view->project?->service_status ?? 'active') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p>未払い・解約にすると店舗管理者のログインとアプリの利用を停止します。</p>
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ ((bool) old('is_active', optional($view->project)->is_active ?? true)) ? 'checked' : '' }}>
                <span>有効化する</span>
            </label>
            @error('is_active')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cash-register"></i> スマレジ連携</h3>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="smaregi_contract_id" class="form-label">契約ID @if($view->requireSmaregi)<span class="text-danger">*</span>@endif</label>
            <input type="text" name="smaregi_contract_id" id="smaregi_contract_id" class="form-control" value="{{ old('smaregi_contract_id', $view->projectSettings['smaregi_contract_id'] ?? '') }}" @if($view->requireSmaregi) required @endif maxlength="255" placeholder="例: sb_skx928r2">
            <p class="form-text text-muted">スマレジ・デベロッパーズの契約IDです。</p>
            @error('smaregi_contract_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="smaregi_client_id" class="form-label">クライアントID @if($view->requireSmaregi)<span class="text-danger">*</span>@endif</label>
            <input type="text" name="smaregi_client_id" id="smaregi_client_id" class="form-control" value="{{ old('smaregi_client_id', $view->projectSettings['smaregi_client_id'] ?? '') }}" @if($view->requireSmaregi) required @endif maxlength="255">
            @error('smaregi_client_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="smaregi_client_secret" class="form-label">クライアントシークレット @if($view->requireSmaregi)<span class="text-danger">*</span>@endif</label>
            <input type="password" name="smaregi_client_secret" id="smaregi_client_secret" class="form-control" value="" @if($view->requireSmaregi) required @endif maxlength="512" autocomplete="new-password" placeholder="{{ $view->project ? '変更する場合のみ入力' : '' }}">
            <p class="form-text text-muted">作成時または再発行時のみスマレジ側に表示されます。リダイレクトURIは <code>urn:ietf:wg:oauth:2.0:oob</code> で問題ありません。</p>
            @error('smaregi_client_secret')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
    </div>
</div>

@if($view->project)
<div class="card mt-3" id="line-connection">
    <div class="card-header">
        <h3 class="card-title"><i class="fab fa-line"></i> LINE接続・通知API</h3>
    </div>
    <div class="card-body">
        <div class="form-group">
            <span class="form-label">LIFF エントリURL</span>
            <p><code>{{ $view->projectSettings['liff_entry_url'] ?? '' }}</code></p>
            <p class="form-text text-muted">LINE Developers の LIFF の「エンドポイントURL」に設定します。</p>
        </div>
        <div class="form-group">
            <span class="form-label">Webhook URL</span>
            <p><code>{{ $view->projectSettings['line_webhook_url'] ?? '' }}</code></p>
            <p class="form-text text-muted">LINE Developers の Messaging API 設定で「Webhook URL」に設定し、「Webhookの利用」をONにします。ブロックの記録に使います。</p>
        </div>
        <div class="form-group">
            <label for="line_liff_id" class="form-label">LIFF ID <span class="text-danger">*</span></label>
            <input type="text" name="line_liff_id" id="line_liff_id" class="form-control" value="{{ old('line_liff_id', $view->projectSettings['line_liff_id'] ?? '') }}" required maxlength="128" placeholder="例: 2008962885-44xGRSQL">
            @error('line_liff_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="line_liff_url" class="form-label">LIFF URL <span class="text-danger">*</span></label>
            <input type="url" name="line_liff_url" id="line_liff_url" class="form-control" value="{{ old('line_liff_url', $view->projectSettings['line_liff_url'] ?? '') }}" required maxlength="2048" placeholder="例: https://miniapp.line.me/2008962885-44xGRSQL">
            @error('line_liff_url')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="line_friend_add_url" class="form-label">LINE友だち追加URL</label>
            <input type="url" name="line_friend_add_url" id="line_friend_add_url" class="form-control" value="{{ old('line_friend_add_url', $view->projectSettings['line_friend_add_url'] ?? '') }}" maxlength="2048" placeholder="例: https://line.me/R/ti/p/@xxxx">
            <p class="form-text text-muted">アプリ起動時の友だち追加の案内と、会員管理の招待QRに使います。</p>
            @error('line_friend_add_url')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        @foreach([
            'line_channel_access_token' => ['チャネルアクセストークン', 'has_line_channel_access_token', 1024, 'お知らせ配信・送信数の表示に使います。'],
            'line_channel_secret' => ['チャネルシークレット', 'has_line_channel_secret', 255, 'Webhookを使う場合は必須です。LINE Developers の Messaging API チャネルの「チャネル基本設定」にあります。'],
        ] as $field => [$label, $flag, $max, $help])
        <div class="form-group">
            <label for="{{ $field }}" class="form-label">{{ $label }}</label>
            <input type="password" name="{{ $field }}" id="{{ $field }}" class="form-control" value="{{ old($field) }}" maxlength="{{ $max }}" autocomplete="new-password" placeholder="{{ ($view->projectSettings[$flag] ?? '') !== '' ? '登録済み（変更する場合のみ入力）' : '未登録' }}">
            <p class="form-text text-muted">{{ $help }}</p>
            @error($field)<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        @endforeach
    </div>
</div>
@endif

@if($view->showAdminAccount)
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-shield"></i> 管理者アカウント</h3>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="admin_name" class="form-label">管理者名 <span class="text-danger">*</span></label>
            <input type="text" name="admin_name" id="admin_name" class="form-control" value="{{ old('admin_name', '') }}" required maxlength="255">
            @error('admin_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="admin_login_id" class="form-label">ログインID <span class="text-danger">*</span></label>
            <input type="text" name="admin_login_id" id="admin_login_id" class="form-control" value="{{ old('admin_login_id', '') }}" required maxlength="100" pattern="[a-zA-Z0-9_-]+" autocomplete="username">
            <p class="form-text text-muted">英数字・ハイフン・アンダースコア。</p>
            @error('admin_login_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="admin_email" class="form-label">メールアドレス <span class="text-danger">*</span></label>
            <input type="email" name="admin_email" id="admin_email" class="form-control" value="{{ old('admin_email', '') }}" required maxlength="255" autocomplete="email">
            @error('admin_email')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="admin_password" class="form-label">パスワード <span class="text-danger">*</span></label>
            <input type="password" name="admin_password" id="admin_password" class="form-control" required autocomplete="new-password">
            @error('admin_password')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="admin_password_confirmation" class="form-label">パスワード（確認） <span class="text-danger">*</span></label>
            <input type="password" name="admin_password_confirmation" id="admin_password_confirmation" class="form-control" required autocomplete="new-password">
        </div>
    </div>
</div>
@endif

@if(! $view->isOnboarding && ! $view->project)
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fab fa-line"></i> LINE連携（必須）</h3>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="line_liff_id" class="form-label">LIFF ID <span class="text-danger">*</span></label>
            <input type="text" name="line_liff_id" id="line_liff_id" class="form-control" value="{{ old('line_liff_id', $view->projectSettings['line_liff_id'] ?? '') }}" required maxlength="128" placeholder="例: 2008962885-44xGRSQL">
            @error('line_liff_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="line_liff_url" class="form-label">LIFF URL <span class="text-danger">*</span></label>
            <input type="url" name="line_liff_url" id="line_liff_url" class="form-control" value="{{ old('line_liff_url', $view->projectSettings['line_liff_url'] ?? '') }}" required maxlength="2048" placeholder="例: https://miniapp.line.me/2008962885-44xGRSQL">
            @error('line_liff_url')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-store"></i> フロント初期表示（必須）</h3>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="frontend_header_text" class="form-label">ヘッダーテキスト <span class="text-danger">*</span></label>
            <input type="text" name="frontend_header_text" id="frontend_header_text" class="form-control" value="{{ old('frontend_header_text', $view->projectSettings['frontend_header_text'] ?? '') }}" required maxlength="255" placeholder="例: デフォルト店舗">
            @error('frontend_header_text')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        @include('admin.partials._frontend_logo_dropzone', [
            'logoLabel' => 'ヘッダー画像（ロゴ）',
            'logoCurrentUrl' => (string) ($view->projectSettings['frontend_logo_url'] ?? ''),
            'logoHelp' => 'LIFF各画面の左上に表示するロゴです。未設定の場合は店舗名の頭文字を表示します。',
        ])
    </div>
</div>

@endif
