<div class="card">
    <div class="card-header"><h2 class="card-title">ホームレイアウト</h2></div>
    <div class="card-body">
        <div class="form-group">
            <label class="form-label" for="home-content-layout">ホームレイアウト</label>
            <select id="home-content-layout" name="home_content[layout]" class="form-control">
                @foreach(['legacy' => '画像スライダー・メニュー', 'gallery' => 'ギャラリー型', 'member_news' => 'ボタン・ニュース', 'buttons_gallery' => 'ボタン2列・1行・ギャラリー'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('home_content.layout', $homeContent['layout'] ?? 'legacy') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('home_content.layout')<p class="text-danger">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <label class="form-label" for="home-content-limit">ギャラリーの表示枚数</label>
            <input id="home-content-limit" type="number" name="home_content[image_limit]" class="form-control" min="1" max="1000" placeholder="すべて" value="{{ old('home_content.image_limit', $homeContent['image_limit'] ?? '') }}">
            <p class="section-help">空欄ですべて表示。ギャラリーの並び順で、先頭から指定枚数を表示します。ボタン2列・1行の配置では、下の「ホームに出す項目」の先頭2件を使います。</p>
            @error('home_content.image_limit')<p class="text-danger">{{ $message }}</p>@enderror
        </div>
        <h3>ギャラリー画像</h3><p class="section-help">各画像の内容・並び順・タップ動作をまとめて設定できます。番号の小さい順に表示します。</p>
        <a class="btn btn-secondary" href="{{ admin_route('admin.gallery.items') }}">画像を追加・差し替え</a>
        <div style="margin-top:16px" aria-label="画像ごとのタップ動作">
            @forelse($homeGalleryItems as $item)
                @php($mode = old("home_content.actions.{$item->id}.mode", $homeContent['actions'][$item->id]['mode'] ?? 'detail'))
                <div class="home-gallery-action" data-gallery-id="{{ $item->id }}">
                    <img src="{{ $item->image_url }}" alt="{{ $item->title }}">
                    <strong>{{ $item->title ?: '画像 '.$loop->iteration }}</strong>
                    <label for="gallery-title-{{ $item->id }}">タイトル</label>
                    <input id="gallery-title-{{ $item->id }}" name="home_gallery[{{ $item->id }}][title]" class="form-control" maxlength="150" value="{{ old("home_gallery.{$item->id}.title", $item->title) }}">
                    <label for="gallery-description-{{ $item->id }}">詳細の説明</label>
                    <textarea id="gallery-description-{{ $item->id }}" name="home_gallery[{{ $item->id }}][description]" class="form-control" maxlength="1000" rows="2">{{ old("home_gallery.{$item->id}.description", $item->description) }}</textarea>
                    <label for="gallery-order-{{ $item->id }}">並び順</label>
                    <input id="gallery-order-{{ $item->id }}" type="number" name="home_gallery[{{ $item->id }}][sort_order]" class="form-control" min="0" max="999" value="{{ old("home_gallery.{$item->id}.sort_order", $item->sort_order) }}">
                    @error("home_gallery.{$item->id}.title")<p class="text-danger">{{ $message }}</p>@enderror
                    @error("home_gallery.{$item->id}.description")<p class="text-danger">{{ $message }}</p>@enderror
                    @error("home_gallery.{$item->id}.sort_order")<p class="text-danger">{{ $message }}</p>@enderror

                    <label for="gallery-mode-{{ $item->id }}">タップ動作</label>
                    <select id="gallery-mode-{{ $item->id }}" name="home_content[actions][{{ $item->id }}][mode]" class="form-control">
                        @foreach(['detail' => '詳細を表示', 'webview' => 'WebView（アプリ内）', 'external' => '外部ブラウザ'] as $value => $label)
                            <option value="{{ $value }}" @selected($mode === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <label for="gallery-url-{{ $item->id }}">遷移先URL（https）</label>
                    <input type="url" id="gallery-url-{{ $item->id }}" name="home_content[actions][{{ $item->id }}][url]" value="{{ old("home_content.actions.{$item->id}.url", $homeContent['actions'][$item->id]['url'] ?? '') }}" class="form-control" placeholder="https://example.com" maxlength="1000">
                    @error("home_content.actions.{$item->id}.url")<p class="text-danger">{{ $message }}</p>@enderror
                </div>
            @empty
                <p class="section-help">ギャラリーに画像を追加すると、ここで設定できます。</p>
            @endforelse
        </div>
    </div>
</div>
@push('scripts')
<script>
(function () {
    const items = {{ Illuminate\Support\Js::from($homeGalleryItems->map(fn ($item) => ['id' => $item->id, 'image_url' => $item->image_url, 'title' => $item->title, 'description' => $item->description])->values()) }};
    const form = document.getElementById('home-settings-form');
    const frame = document.getElementById('home-live-preview');
    const initial = {{ Illuminate\Support\Js::from(app(\App\Services\AppConfigService::class)->buildForProjectKey((string) request()->route('project_key'))) }};
    function render() {
        const layout = document.getElementById('home-content-layout').value;
        const limit = Number(document.getElementById('home-content-limit').value) || items.length;
        document.querySelectorAll('[data-gallery-id]').forEach(row => {
            const mode = row.querySelector('select').value;
            const input = row.querySelector('input[type=url]');
            input.required = mode !== 'detail'; input.hidden = mode === 'detail';
            row.querySelector('label[for="' + input.id + '"]').hidden = mode === 'detail';
        });
        const config = JSON.parse(JSON.stringify(initial));
        config.home.layout = layout;
        config.home.show_member_card = form.querySelector('input[type=checkbox][name=home_show_member_card]').checked;
        config.home.menu_layout = document.getElementById('home_menu_layout').value;
        config.home.gallery_items = items.map(item => ({ ...item, title: document.getElementById('gallery-title-' + item.id).value, description: document.getElementById('gallery-description-' + item.id).value, sort_order: Number(document.getElementById('gallery-order-' + item.id).value), mode: document.getElementById('gallery-mode-' + item.id).value, url: document.getElementById('gallery-url-' + item.id).value })).sort((a, b) => a.sort_order - b.sort_order || a.id - b.id).slice(0, limit);
        config.tabs = Array.from(form.querySelectorAll('.home-settings-row')).filter(row => row.querySelector('.home-settings-enabled-cb').checked).map(row => {
            const key = row.querySelector('input[name$="[tab]"]').value;
            const saved = initial.tabs.find(tab => tab.key === key) || {};
            return { ...saved, key, label: row.querySelector('input[name$="[label]"]')?.value || row.querySelector('.home-sort-label')?.textContent, icon: row.querySelector('select.home-sort-icon').value };
        });
        frame?.dispatchEvent(new CustomEvent('app-preview:draft', { detail: config }));
        frame?.contentWindow?.postMessage({ type: 'reggit:designer-preview:navigate', path: '/home' }, window.location.origin);
    }
    form.addEventListener('input', render); form.addEventListener('change', render); form.addEventListener('drop', () => requestAnimationFrame(render));
    render();
})();
</script>
@endpush
