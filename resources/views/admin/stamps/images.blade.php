@extends('admin.layout')
@section('title', 'スタンプ画像')
@section('page-title', 'スタンプ画像')

@section('content')
@php
    $required = max(1, (int) $stampCard->required_stamps);
    $rewardCounts = collect($stampCard->reward_milestones ?? [])->pluck('stamp_count')->map(fn ($count) => (int) $count)->all();
    $previewCard = [
        'id' => 'preview',
        'name' => $stampCard->name,
        'required_stamps' => $required,
        'current_stamps' => max(1, (int) round($required * 0.4)),
        'image_url' => $stampCard->image_url,
        'reward_description' => $stampCard->reward_description,
        'layout_style' => $stampCard->layout_style ?? 'grid',
        'design' => $stampCard->design ?? 'standard',
        'valid_until' => $stampCard->valid_until?->format('Y-m-d'),
        'reward_milestones' => collect($stampCard->reward_milestones ?? [])->map(fn ($milestone) => ['stamp_count' => (int) ($milestone['stamp_count'] ?? 0), 'description' => (string) ($milestone['description'] ?? '')])->values()->all(),
    ];
@endphp
<nav class="breadcrumb">
    <a href="{{ admin_route('admin.stamps') }}">スタンプ</a><span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.stamps.edit', ['id' => $stampCard->id]) }}">{{ $stampCard->name }}</a><span class="breadcrumb-sep">/</span>
    <span>スタンプ画像</span>
</nav>

<form action="{{ admin_route('admin.stamps.images.update', ['id' => $stampCard->id]) }}" method="POST" class="stamp-images-page" data-stamp-images-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="stamp_images_data" id="stampImagesData">
    <input type="hidden" name="stamp_empty_images_data" id="stampEmptyImagesData">
    <input type="file" id="stampImageFile" accept="image/png,image/jpeg,image/gif,image/webp" hidden>

    <section class="card">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-images"></i> {{ $stampCard->name }}（全{{ $required }}個）</h3></div>
        <div class="card-body">
            <p class="form-text text-muted">各スタンプの「押す前」「押したあと」を押して画像を選ぶか、画像をドラッグ＆ドロップします（PNG / JPG 2MBまで）。未設定のところは、押す前は番号、押したあとは★で表示します。<i class="fas fa-gift" aria-hidden="true"></i> は達成特典のあるスタンプです。</p>
            <div class="sim-bulk">
                <span>まとめて設定：</span>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bulk-kind="empty"><i class="fas fa-copy"></i> 押す前を全スタンプに</button>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bulk-kind="filled"><i class="fas fa-copy"></i> 押したあとを全スタンプに</button>
                <button type="button" class="btn btn-sm btn-outline-danger" data-bulk-clear><i class="fas fa-eraser"></i> すべて消す</button>
            </div>
            <ol class="sim-grid" id="stampImagesGrid" aria-label="スタンプごとの画像"></ol>
        </div>
    </section>

    <aside class="card sim-preview">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-wand-magic-sparkles"></i> ライブプレビュー</h3></div>
        <div class="card-body">
            <x-admin.app-preview id="stampLivePreview" title="実際の会員アプリのスタンプカード画面" path="/stamp" kind="stamp" />
        </div>
    </aside>

    <div class="form-actions sim-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
        <x-back-link :href="admin_route('admin.stamps.edit', ['id' => $stampCard->id])" label="カードの編集に戻る" />
    </div>
</form>

<script>
(() => {
    const required = {{ $required }};
    const rewardCounts = new Set(@json($rewardCounts));
    const previewCard = @json($previewCard);
    const images = { filled: @json((object) ($stampCard->stamp_images ?? [])), empty: @json((object) ($stampCard->stamp_empty_images ?? [])) };
    const labels = { empty: '押す前', filled: '押したあと' };
    const grid = document.getElementById('stampImagesGrid');
    const fileInput = document.getElementById('stampImageFile');
    const previewFrame = document.getElementById('stampLivePreview');
    let target = null;

    const readFile = (file, done) => {
        if (!file || !file.type.match(/^image\//)) return;
        if (file.size > 2 * 1024 * 1024) { alert('画像サイズは2MB以下にしてください'); return; }
        const reader = new FileReader();
        reader.onload = () => done(reader.result);
        reader.readAsDataURL(file);
    };

    const well = (kind, pos) => {
        const box = document.createElement('div');
        box.className = 'sim-well';
        box.dataset.kind = kind;
        box.dataset.pos = String(pos);
        const pick = document.createElement('button');
        pick.type = 'button';
        pick.className = 'sim-well-pick';
        pick.setAttribute('aria-label', `スタンプ${pos}の${labels[kind]}の画像を選ぶ`);
        const src = images[kind][pos];
        if (src) {
            const img = document.createElement('img');
            img.src = src;
            img.alt = '';
            pick.append(img);
        } else {
            const placeholder = document.createElement('span');
            placeholder.className = kind === 'filled' ? 'fas fa-star' : 'sim-well-num';
            if (kind === 'empty') placeholder.textContent = String(pos);
            pick.append(placeholder);
        }
        const caption = document.createElement('small');
        caption.textContent = labels[kind];
        box.append(pick, caption);
        if (src) {
            const clear = document.createElement('button');
            clear.type = 'button';
            clear.className = 'sim-well-clear';
            clear.setAttribute('aria-label', `スタンプ${pos}の${labels[kind]}の画像を消す`);
            clear.textContent = '×';
            box.append(clear);
        }
        return box;
    };

    const render = () => {
        const tiles = [];
        for (let pos = 1; pos <= required; pos++) {
            const tile = document.createElement('li');
            tile.className = 'sim-tile';
            const head = document.createElement('p');
            head.className = 'sim-tile-head';
            head.textContent = `${pos}個目`;
            if (rewardCounts.has(pos)) {
                const gift = document.createElement('i');
                gift.className = 'fas fa-gift';
                gift.title = '達成特典あり';
                head.append(' ', gift);
            }
            const wells = document.createElement('div');
            wells.className = 'sim-wells';
            wells.append(well('empty', pos), well('filled', pos));
            tile.append(head, wells);
            tiles.push(tile);
        }
        grid.replaceChildren(...tiles);
        // 空の {} を送るとサーバーは画像をすべて消す
        document.getElementById('stampImagesData').value = JSON.stringify(images.filled);
        document.getElementById('stampEmptyImagesData').value = JSON.stringify(images.empty);
        previewFrame?.dispatchEvent(new CustomEvent('app-preview:draft', { detail: { ...previewCard, stamp_images: images.filled, stamp_empty_images: images.empty } }));
    };

    const set = (kind, positions, src) => {
        positions.forEach((pos) => { if (src) images[kind][pos] = src; else delete images[kind][pos]; });
        render();
    };
    const all = () => Array.from({ length: required }, (_, index) => index + 1);

    grid.addEventListener('click', (event) => {
        const box = event.target.closest('.sim-well');
        if (!box) return;
        if (event.target.closest('.sim-well-clear')) { set(box.dataset.kind, [Number(box.dataset.pos)], null); return; }
        target = { kind: box.dataset.kind, positions: [Number(box.dataset.pos)] };
        fileInput.click();
    });
    grid.addEventListener('dragover', (event) => {
        const box = event.target.closest('.sim-well');
        if (box) { event.preventDefault(); box.classList.add('is-dragover'); }
    });
    grid.addEventListener('dragleave', (event) => event.target.closest('.sim-well')?.classList.remove('is-dragover'));
    grid.addEventListener('drop', (event) => {
        const box = event.target.closest('.sim-well');
        if (!box) return;
        event.preventDefault();
        readFile(event.dataTransfer?.files?.[0], (src) => set(box.dataset.kind, [Number(box.dataset.pos)], src));
    });
    document.querySelectorAll('[data-bulk-kind]').forEach((button) => button.addEventListener('click', () => {
        target = { kind: button.dataset.bulkKind, positions: all() };
        fileInput.click();
    }));
    document.querySelector('[data-bulk-clear]').addEventListener('click', () => {
        if (!confirm('すべてのスタンプ画像を消しますか？（保存するまで反映されません）')) return;
        images.filled = {};
        images.empty = {};
        render();
    });
    fileInput.addEventListener('change', () => {
        const chosen = target;
        readFile(fileInput.files[0], (src) => { if (chosen) set(chosen.kind, chosen.positions, src); });
        fileInput.value = '';
    });

    render();
})();
</script>
@endsection

@push('styles')
<style>
.stamp-images-page { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 24px; align-items: start; }
.sim-preview { position: sticky; top: 90px; }
.sim-actions { grid-column: 1 / -1; }
.sim-bulk { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin: 12px 0 16px; font-size: 13px; color: #475569; }
.sim-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(168px, 1fr)); gap: 12px; margin: 0; padding: 0; list-style: none; }
.sim-tile { padding: 10px; border: 1px solid #dbe4ee; border-radius: 12px; background: #fff; }
.sim-tile-head { display: flex; align-items: center; gap: 4px; margin: 0 0 8px; font-size: 13px; font-weight: 700; color: #0f172a; }
.sim-tile-head .fa-gift { color: #f97316; }
.sim-wells { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.sim-well { position: relative; display: grid; justify-items: center; gap: 4px; }
.sim-well small { color: #64748b; font-size: 11px; }
.sim-well-pick { display: grid; place-items: center; width: 64px; height: 64px; padding: 0; overflow: hidden; border: 2px dashed #cbd5e1; border-radius: 50%; background: #f8fafc; color: #94a3b8; font-size: 14px; font-weight: 700; cursor: pointer; }
.sim-well-pick:hover, .sim-well.is-dragover .sim-well-pick { border-color: #3b82f6; background: #eff6ff; }
.sim-well-pick:focus-visible { outline: 3px solid rgba(37, 99, 235, .35); outline-offset: 2px; }
.sim-well-pick img { width: 100%; height: 100%; object-fit: cover; }
.sim-well-pick:has(img) { border-style: solid; border-color: #22c55e; }
.sim-well-clear { position: absolute; top: -4px; right: 6px; width: 22px; height: 22px; padding: 0; border: 1px solid #fff; border-radius: 50%; background: #ef4444; color: #fff; font-size: 14px; line-height: 1; cursor: pointer; }
@media (max-width: 1100px) {
    .stamp-images-page { grid-template-columns: 1fr; }
    .sim-preview { position: static; }
}
</style>
@endpush
