@extends('admin.layout')
@section('title', '店舗向けのお知らせ')
@section('page-title', '店舗向けのお知らせ')
@section('content')
<div class="publishing-header">
    <div><p class="form-text">メンテナンスや新機能の情報を、店舗管理画面に届けます。</p></div>
    <a href="#notice-create" class="btn btn-primary" id="notice-create-button"><i class="fas fa-plus" aria-hidden="true"></i> お知らせを作成</a>
</div>
@if($errors->any())<div class="alert alert-error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<details id="notice-create" class="card publishing-disclosure" @if($errors->any() && old('_notice_id') === 'new') open @endif>
    <summary><span><i class="fas fa-pen" aria-hidden="true"></i> 新しいお知らせ</span><span class="form-text">作成フォームを開く</span></summary>
    <div class="card-body">
        <form method="POST" action="{{ route('system-admin.notices.store') }}">
            @csrf
            <input type="hidden" name="_notice_id" value="new">
            @include('admin.partials.system-notice-form', ['notice' => null])
            <div class="publishing-actions"><button class="btn btn-primary">お知らせを保存</button><p class="form-text">公開する場合は、公開設定と期間を確認してください。</p></div>
        </form>
    </div>
</details>
<div class="publishing-list-heading"><h2>お知らせ一覧 <span class="publishing-count">{{ $notices->total() }}件</span></h2><p class="form-text">記事を開くと内容の編集・プレビューができます。</p></div>
@forelse($notices as $notice)
@php
    if (!$notice->is_published) { $state = '下書き'; $stateClass = ''; }
    elseif ($notice->ends_at && !$notice->ends_at->isFuture()) { $state = '公開終了'; $stateClass = ''; }
    elseif ($notice->starts_at?->isFuture()) { $state = '公開予約'; $stateClass = 'is-scheduled'; }
    else { $state = '公開中'; $stateClass = 'is-live'; }
@endphp
<details class="card publishing-disclosure" @if($errors->any() && old('_notice_id') === (string) $notice->id) open @endif>
    <summary>
        <span class="publishing-notice-summary"><span class="publishing-badge {{ $stateClass }}">{{ $state }}</span><strong>{{ $notice->title }}</strong><span class="form-text">公開期間：{{ $notice->starts_at?->format('Y/m/d H:i') ?? '即時' }} 〜 {{ $notice->ends_at?->format('Y/m/d H:i') ?? '無期限' }}</span></span>
        <span class="publishing-edit-label">編集 <i class="fas fa-chevron-down" aria-hidden="true"></i></span>
    </summary>
    <div class="card-body">
        <form method="POST" action="{{ route('system-admin.notices.update', $notice->id) }}">
            @csrf @method('PUT')
            <input type="hidden" name="_notice_id" value="{{ $notice->id }}">
            @include('admin.partials.system-notice-form')
            <div class="publishing-actions"><button class="btn btn-primary">変更を保存</button></div>
        </form>
        <form class="publishing-delete" method="POST" action="{{ route('system-admin.notices.destroy', $notice->id) }}" onsubmit="return confirm('このお知らせを削除しますか？')">@csrf @method('DELETE')<button class="btn btn-danger">このお知らせを削除</button></form>
    </div>
</details>
@empty
<div class="card publishing-empty"><i class="fas fa-bullhorn" aria-hidden="true"></i><h2>お知らせはまだありません</h2><p>「お知らせを作成」から、店舗に共有したい情報を書きましょう。</p></div>
@endforelse
{{ $notices->links() }}
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/components/14-publishing.css') }}">
@endpush

@push('scripts')
<script>
document.getElementById('notice-create-button').addEventListener('click', () => { document.getElementById('notice-create').open = true; });
document.querySelectorAll('.notice-editor').forEach(editor => {
    const button = editor.querySelector('[data-notice-preview]');
    const panel = editor.querySelector('[data-notice-preview-panel]');
    let sequence = 0;
    let timer;
    async function renderPreview() {
        const request = ++sequence;
        const form = editor.closest('form');
        const error = panel.querySelector('[data-preview-error]');
        panel.hidden = false;
        panel.setAttribute('aria-busy', 'true');
        panel.querySelector('[data-preview-title]').textContent = form.querySelector('[name=title]').value;
        error.hidden = true;
        try {
            const response = await fetch(@json(route('system-admin.notices.preview')), {
                method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name=_token]').value},
                body: JSON.stringify({body: editor.querySelector('textarea').value})
            });
            if (!response.ok) throw new Error('プレビューを取得できませんでした。本文は20,000文字以内で入力し、再度お試しください。');
            const data = await response.json();
            if (request === sequence) panel.querySelector('[data-preview-body]').innerHTML = data.html;
        } catch (e) {
            if (request === sequence) { error.textContent = e.message; error.hidden = false; panel.querySelector('[data-preview-body]').replaceChildren(); }
        } finally {
            if (request === sequence) panel.removeAttribute('aria-busy');
        }
    }
    button.addEventListener('click', renderPreview);
    editor.closest('form').addEventListener('input', () => {
        if (panel.hidden) return;
        ++sequence;
        clearTimeout(timer);
        timer = setTimeout(renderPreview, 300);
    });
});
</script>
@endpush
