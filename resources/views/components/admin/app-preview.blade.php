{{-- 管理画面で実際の店舗アプリを iframe で表示する。保存前の入力内容は、iframe に app-preview:draft イベントを送ると反映される。 --}}
@props(['id', 'title', 'path', 'kind', 'query' => []])
@php
    $projectKey = request()->route('project_key');
    $previewUrl = is_string($projectKey) && $projectKey !== ''
        ? route('liff.project', ['project_key' => $projectKey], false).'?'.http_build_query(['designer_live_preview' => 1, 'designer_embed' => 1, ...$query]).'#'.$path
        : null;
@endphp
<div class="cp-real-preview-stage" aria-label="実際の会員アプリのプレビュー">
    @if($previewUrl !== null)
        <div class="cp-device-shell">
            <span class="cp-device-button cp-device-button--silent" aria-hidden="true"></span>
            <span class="cp-device-button cp-device-button--volume-up" aria-hidden="true"></span>
            <span class="cp-device-button cp-device-button--volume-down" aria-hidden="true"></span>
            <span class="cp-device-button cp-device-button--power" aria-hidden="true"></span>
            <div class="cp-real-preview-frame">
                <div class="cp-real-preview-screen">
                    <iframe id="{{ $id }}" title="{{ $title }}" src="{{ $previewUrl }}" loading="eager"></iframe>
                </div>
            </div>
        </div>
        <p class="cp-real-preview-note"><i class="fas fa-circle-check" aria-hidden="true"></i> 店舗アプリと同じ画面・デザインです</p>
    @else
        <div class="cp-real-preview-unavailable">
            <i class="fas fa-mobile-screen" aria-hidden="true"></i>
            <p>店舗アプリのURLを取得できませんでした。</p>
        </div>
    @endif
</div>
@if($previewUrl !== null)
<script>
(() => {
    const frame = document.getElementById(@js($id));
    const kind = @js($kind);
    let draft;
    const send = () => {
        if (draft !== undefined) frame.contentWindow?.postMessage({ type: `reggit:${kind}-form-preview:update`, draft }, window.location.origin);
    };
    frame.addEventListener('app-preview:draft', (event) => { draft = event.detail; send(); });
    // iframe の読み込み直後やアプリの準備完了時は、まだ受け取れていないため送り直す
    frame.addEventListener('load', send);
    window.addEventListener('message', (event) => {
        if (event.origin === window.location.origin && event.source === frame.contentWindow && event.data?.type === `reggit:${kind}-form-preview:ready`) send();
    });
})();
</script>
@endif
