@extends('admin.layout')
@section('title', '店舗向けのお知らせ')
@section('page-title', '店舗向けのお知らせ')
@section('content')
<h1>店舗向けのお知らせ</h1>
@if($errors->any())<div class="alert alert-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<p>公開中のお知らせは店舗管理画面のダッシュボードに表示されます。</p>
<div class="card"><div class="card-body">
<h2>お知らせを作成</h2>
<form method="POST" action="{{ route('system-admin.notices.store') }}">
@csrf
@include('admin.partials.system-notice-form', ['notice' => null])
<button class="btn btn-primary">保存</button>
</form></div></div>
@foreach($notices as $notice)
<div class="card mt-3"><div class="card-body">
<p><strong>公開状態：</strong>
@if(!$notice->is_published) 下書き（店舗には表示されません）
@elseif($notice->starts_at?->isFuture()) 公開予約
@elseif($notice->ends_at && !$notice->ends_at->isFuture()) 公開終了
@else 公開中（店舗ダッシュボードに表示）
@endif
</p>
<form method="POST" action="{{ route('system-admin.notices.update', $notice->id) }}">
@csrf @method('PUT')
@include('admin.partials.system-notice-form')
<button class="btn btn-primary">更新</button>
</form>
<form method="POST" action="{{ route('system-admin.notices.destroy', $notice->id) }}" onsubmit="return confirm('このお知らせを削除しますか？')">@csrf @method('DELETE')<button class="btn btn-danger mt-3">削除</button></form>
</div></div>
@endforeach
{{ $notices->links() }}
@endsection

@push('scripts')
<script>
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
