@extends('admin.layout')

@section('title', $article === null ? 'ヘルプを追加' : 'ヘルプを編集')
@section('page-title', $article === null ? 'ヘルプを追加' : 'ヘルプを編集')

@section('content')
<p class="help-back"><a href="{{ route('system-admin.help-articles.index') }}">一覧へ戻る</a></p>
<div class="card">
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
                <label for="slug" class="form-label">URL用のスラッグ</label>
                <input id="slug" name="slug" class="form-control" value="{{ old('slug', $article->slug ?? '') }}" required maxlength="80" pattern="[a-z0-9]+(-[a-z0-9]+)*">
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
                <textarea id="body" name="body" class="form-control" rows="16" required>{{ old('body', $article->body ?? '') }}</textarea>
                <p class="form-text">見出しは ##、箇条書きは行頭の -、強調は **文字** です。</p>
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
            <button type="submit" class="btn btn-primary">保存</button>
        </form>
        @if($article !== null)
        <form method="POST" action="{{ route('system-admin.help-articles.destroy', ['id' => $article->id]) }}" onsubmit="return confirm('削除しますか？');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">削除</button>
        </form>
        @endif
    </div>
</div>
@endsection
