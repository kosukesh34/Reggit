@extends('admin.layout')
@section('title', 'アプリデザイン')
@section('page-title', 'アプリデザイン')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/coupons.css') }}">
<style>.member-page-editor {display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:24px;align-items:start}.member-page-preview {position:sticky;top:20px}@media(max-width:1000px){.member-page-editor{grid-template-columns:1fr}.member-page-preview{position:static;max-width:300px;margin:auto}}</style>
@endpush
@section('content')
@include('admin.settings._app-design-navigation', ['activeDesign' => 'card'])
<form action="{{ admin_route('admin.settings.member-card.update') }}" method="POST" class="member-card-settings">
    @csrf
    <div class="member-page-editor"><div>
    @include('admin.settings._member-card-fields')
    </div><aside class="member-page-preview">
    <h3>会員証ページのプレビュー</h3><p>変更は保存前に反映されます。会員情報はプレビュー用です。</p>
    <div class="cp-real-preview-stage"><div class="cp-device-shell">
    <span class="cp-device-button cp-device-button--silent" aria-hidden="true"></span><span class="cp-device-button cp-device-button--volume-up" aria-hidden="true"></span><span class="cp-device-button cp-device-button--volume-down" aria-hidden="true"></span><span class="cp-device-button cp-device-button--power" aria-hidden="true"></span>
    <div class="cp-real-preview-frame"><div class="cp-real-preview-screen"><iframe id="member-page-preview" title="実際の会員証ページ" src="{{ route('liff.project', ['project_key' => request()->route('project_key')], false) }}?designer_live_preview=1&amp;designer_embed=1&amp;designer_source=member#/membership"></iframe></div></div>
    </div></div></aside></div>
    <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 会員証デザインを保存</button></div>
</form>
@push('scripts')
<script>
(function(){
    const frame = document.getElementById('member-page-preview');
    const form = document.querySelector('.member-card-settings');
    const initial = {{ Illuminate\Support\Js::from(app(\App\Services\AppConfigService::class)->buildForProjectKey((string) request()->route('project_key'))) }};
    function update(){
        const config = JSON.parse(JSON.stringify(initial));
        config.member_screen.style = document.getElementById('member_page_style').value;
        ['rank_progress','point_history','coupon_activity'].forEach(key => {
            config.member_screen['show_' + key] = form.querySelector('input[type=checkbox][name=member_show_' + key + ']').checked;
        });
        frame.contentWindow?.postMessage({type:'reggit:home-form-preview:update',config},window.location.origin);
    }
    form.addEventListener('input',update); form.addEventListener('change',update);
    frame.addEventListener('load',update);
    window.addEventListener('message',event=>{if(event.origin===window.location.origin && event.source===frame.contentWindow && event.data?.type==='reggit:home-form-preview:ready')update();});
})();
</script>
@endpush
@endsection
