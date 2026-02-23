<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-layer-group"></i> 基本情報</h3>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="project_key" class="form-label">プロジェクトキー <span class="text-danger">*</span></label>
            <input type="text" name="project_key" id="project_key" class="form-control" value="{{ old('project_key', optional($project)->project_key ?? '') }}" required maxlength="64" pattern="[a-zA-Z0-9_-]+">
            <p class="form-text text-muted">URLやAPIで利用する識別子です（英数字・ハイフン・アンダースコア）。</p>
            @error('project_key')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="name" class="form-label">プロジェクト名 <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', optional($project)->name ?? '') }}" required maxlength="255">
            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="description" class="form-label">説明</label>
            <input type="text" name="description" id="description" class="form-control" value="{{ old('description', optional($project)->description ?? '') }}" maxlength="255">
            @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="plan" class="form-label">プラン <span class="text-danger">*</span></label>
            <select name="plan" id="plan" class="form-control" required>
                @foreach(\App\Enums\ProjectPlan::getValues() as $value => $label)
                    <option value="{{ $value }}" {{ old('plan', optional($project)->plan ?? 'normal') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('plan')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ ((bool) old('is_active', optional($project)->is_active ?? true)) ? 'checked' : '' }}>
                <span>有効化する</span>
            </label>
            @error('is_active')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fab fa-line"></i> LINE連携（必須）</h3>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="line_liff_id" class="form-label">LIFF ID <span class="text-danger">*</span></label>
            <input type="text" name="line_liff_id" id="line_liff_id" class="form-control" value="{{ old('line_liff_id', $projectSettings['line_liff_id'] ?? '') }}" required maxlength="128" placeholder="例: 2008962885-44xGRSQL">
            @error('line_liff_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="line_liff_url" class="form-label">LIFF URL <span class="text-danger">*</span></label>
            <input type="url" name="line_liff_url" id="line_liff_url" class="form-control" value="{{ old('line_liff_url', $projectSettings['line_liff_url'] ?? '') }}" required maxlength="2048" placeholder="例: https://miniapp.line.me/2008962885-44xGRSQL">
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
            <input type="text" name="frontend_header_text" id="frontend_header_text" class="form-control" value="{{ old('frontend_header_text', $projectSettings['frontend_header_text'] ?? '') }}" required maxlength="255" placeholder="例: デフォルト店舗">
            @error('frontend_header_text')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label for="frontend_logo_url" class="form-label">ロゴ画像URL</label>
            <input type="url" name="frontend_logo_url" id="frontend_logo_url" class="form-control" value="{{ old('frontend_logo_url', $projectSettings['frontend_logo_url'] ?? '') }}" maxlength="2048" placeholder="例: https://example.com/logo.png">
            @error('frontend_logo_url')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clock"></i> 営業設定（必須）</h3>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="business_hours_start" class="form-label">営業時間 開始 <span class="text-danger">*</span></label>
                <input type="time" name="business_hours_start" id="business_hours_start" class="form-control" value="{{ old('business_hours_start', $projectSettings['business_hours_start'] ?? '10:00') }}" required>
                @error('business_hours_start')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group col-md-6">
                <label for="business_hours_end" class="form-label">営業時間 終了 <span class="text-danger">*</span></label>
                <input type="time" name="business_hours_end" id="business_hours_end" class="form-control" value="{{ old('business_hours_end', $projectSettings['business_hours_end'] ?? '22:00') }}" required>
                @error('business_hours_end')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-palette"></i> テーマカラー</h3>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group col-md-3">
                <label for="line_primary_color" class="form-label">メイン色</label>
                <input type="text" name="line_primary_color" id="line_primary_color" class="form-control" value="{{ old('line_primary_color', $projectSettings['line_primary_color'] ?? '#000000') }}" maxlength="7" placeholder="#000000">
                @error('line_primary_color')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group col-md-3">
                <label for="line_primary_dark" class="form-label">メイン色（濃い）</label>
                <input type="text" name="line_primary_dark" id="line_primary_dark" class="form-control" value="{{ old('line_primary_dark', $projectSettings['line_primary_dark'] ?? '#333333') }}" maxlength="7" placeholder="#333333">
                @error('line_primary_dark')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group col-md-3">
                <label for="line_success_color" class="form-label">成功色</label>
                <input type="text" name="line_success_color" id="line_success_color" class="form-control" value="{{ old('line_success_color', $projectSettings['line_success_color'] ?? '#000000') }}" maxlength="7" placeholder="#000000">
                @error('line_success_color')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group col-md-3">
                <label for="line_danger_color" class="form-label">警告色</label>
                <input type="text" name="line_danger_color" id="line_danger_color" class="form-control" value="{{ old('line_danger_color', $projectSettings['line_danger_color'] ?? '#dc3545') }}" maxlength="7" placeholder="#dc3545">
                @error('line_danger_color')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>
</div>
