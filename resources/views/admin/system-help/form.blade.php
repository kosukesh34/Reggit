@extends('admin.layout')

@section('title', $article === null ? 'ヘルプ記事を書く' : 'ヘルプ記事を編集')
@section('page-title', $article === null ? 'ヘルプ記事を書く' : 'ヘルプ記事を編集')

@section('content')
<p class="help-back"><a href="{{ route('system-admin.help-articles.index') }}">一覧へ戻る</a></p>
<div class="card publishing-form">
    <div class="card-body">
        <form method="POST" action="{{ $article === null ? route('system-admin.help-articles.store') : route('system-admin.help-articles.update', ['id' => $article->id]) }}">
            @csrf
            @if($article !== null)
            @method('PUT')
            @endif
            <div class="form-group">
                <label for="title" class="form-label">タイトル</label>
                <input id="title" name="title" class="form-control" value="{{ old('title', $article->title ?? '') }}" required maxlength="255">
                @error('title')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="summary" class="form-label">一覧に出す説明</label>
                <input id="summary" name="summary" class="form-control" value="{{ old('summary', $article->summary ?? '') }}" required maxlength="500">
                @error('summary')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="slug" class="form-label">記事URL（半角英数字とハイフン）</label>
                <input id="slug" name="slug" class="form-control" value="{{ old('slug', $article->slug ?? '') }}" required maxlength="80" pattern="[a-z0-9]+(-[a-z0-9]+)*">
                <p class="form-text">例：coupon-delivery。ほかの記事と重複しない名前を入力してください。</p>
                @error('slug')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="category" class="form-label">区分</label>
                <select id="category" name="category" class="form-control" required>
                    <option value="faq" @selected(old('category', $article->category ?? 'faq') === 'faq')>よくある質問</option>
                    <option value="guide" @selected(old('category', $article->category ?? 'faq') === 'guide')>使い方</option>
                </select>
            </div>
            <div class="form-group">
                <label for="body" class="form-label">本文（Markdown）</label>
                <div class="publishing-toolbar"><button type="button" class="btn btn-secondary" data-help-format="heading">見出し</button><button type="button" class="btn btn-secondary" data-help-format="bold">太字</button><button type="button" class="btn btn-secondary" data-help-format="list">箇条書き</button><button type="button" class="btn btn-secondary" id="help-preview">プレビュー</button></div>
                <textarea id="body" name="body" class="form-control" rows="16" required>{{ old('body', $article->body ?? '') }}</textarea>
                <p class="form-text">見出しは ##、箇条書きは行頭の -、強調は **文字** です。</p>
                <section id="help-preview-panel" class="publishing-preview" hidden aria-live="polite"><p class="form-text">保存前の内容を店舗ヘルプと同じ表示で確認できます。</p><article class="help-manual"><h1 id="help-preview-title"></h1><p id="help-preview-summary" class="help-lead"></p><div id="help-preview-content" class="help-markdown"></div></article></section>
                @error('body')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="sort_order" class="form-label">表示順</label>
                <input id="sort_order" name="sort_order" type="number" class="form-control" min="0" max="9999" value="{{ old('sort_order', $article->sort_order ?? 0) }}">
            </div>
            <div class="form-group">
                @php
                    $publishedValue = old('is_published', ($article->is_published ?? true) ? '1' : '0');
                @endphp
                <label>
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" name="is_published" value="1" @checked($publishedValue === '1' || $publishedValue === 1 || $publishedValue === true)>
                    店舗のヘルプに公開する
                </label>
            </div>
            <button type="submit" class="btn btn-primary">記事を保存</button>
        </form>
        @if($article !== null)
        <form method="POST" action="{{ route('system-admin.help-articles.destroy', ['id' => $article->id]) }}" onsubmit="return confirm('削除しますか？');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">この記事を削除</button>
        </form>
        @endif
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/components/14-publishing.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/components/12-help-manual.css') }}">
@endpush
@push('scripts')
<script>
const helpBody = document.getElementById('body');
document.querySelectorAll('[data-help-format]').forEach(button => button.addEventListener('click', () => {
    const start = helpBody.selectionStart, end = helpBody.selectionEnd;
    const text = helpBody.value.slice(start, end) || '文字を入力';
    const format = button.dataset.helpFormat;
    const prefix = start > 0 && helpBody.value[start - 1] !== '\n' ? '\n' : '';
    const value = format === 'bold' ? '**' + text + '**' : prefix + (format === 'heading' ? '## ' : '- ') + text;
    helpBody.setRangeText(value, start, end, 'select');
    helpBody.focus();
}));
document.getElementById('help-preview').addEventListener('click', async function () {
    const panel = document.getElementById('help-preview-panel');
    const content = document.getElementById('help-preview-content');
    panel.hidden = false;
    document.getElementById('help-preview-title').textContent = document.getElementById('title').value || 'タイトル未入力';
    document.getElementById('help-preview-summary').textContent = document.getElementById('summary').value;
    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    this.disabled = true;
    content.textContent = '読み込み中…';
    try {
        const response = await fetch(@json(route('system-admin.help-articles.preview')), {
            method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': helpBody.form.querySelector('[name=_token]').value},
            body: JSON.stringify({body: helpBody.value})
        });
        if (!response.ok) throw new Error();
        content.innerHTML = (await response.json()).html;
    } catch { content.textContent = 'プレビューを取得できませんでした。本文は100,000文字以内で入力し、再度お試しください。'; }
    finally { this.disabled = false; }
});
</script>
@endpush
