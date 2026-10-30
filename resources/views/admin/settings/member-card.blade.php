@extends('admin.layout')
@section('title', 'アプリデザイン')
@section('page-title', 'アプリデザイン')
@push('styles')
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
    <x-admin.app-preview id="member-page-preview" title="実際の会員証ページ" path="/membership" kind="home" :query="['designer_source' => 'member']" />
    </aside></div>
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
        frame?.dispatchEvent(new CustomEvent('app-preview:draft',{detail:config}));
    }
    form.addEventListener('input',update); form.addEventListener('change',update);
    update();
})();
</script>
@endpush
@endsection
