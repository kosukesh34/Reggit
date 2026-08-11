@extends('admin.layout')

@section('title', 'LIFFデザイン')
@section('page-title', 'LIFFデザイン')
@section('page-kind', 'liff-designer')

@section('content')
<form action="{{ admin_route('admin.settings.liff-designer.update') }}" method="POST" id="liffDesignerForm" hidden>
    @csrf
    <input type="hidden" name="layouts_json" id="layoutsJson">
    <input type="hidden" name="detail_layouts_json" id="detailLayoutsJson">
    <input type="hidden" name="custom_css" id="customCssInput" value="{{ $customCss ?? '' }}">
    <input type="hidden" name="tab_settings_json" id="tabSettingsJson">
    <input type="hidden" name="menu_items_json" id="menuItemsJson">
</form>

<div id="liff-designer-root"></div>

@php
    $ldDesignerConfig = [
        'tabSettings' => $tabSettings ?? [],
        'customCss' => $customCss ?? '',
        'menuItems' => $menuItems ?? [],
        'formAction' => admin_route('admin.settings.liff-designer.update'),
        'csrfToken' => csrf_token(),
        'backUrl' => admin_route('admin.settings.basic'),
    ];
@endphp
<script>
    window.__PROJECT_KEY__ = @json(request()->route('project_key') ?? 'default');
    window.__FRONTEND_HEADER__ = {
        title: @json(($tabSettings['_global']['app_display_name'] ?? null) ?: config('app.name')),
        logoUrl: null,
        friendAddUrl: @json($lineFriendAddUrl ?? ''),
    };
    window.__LIFF_TAB_SETTINGS__ = @json($tabSettings ?? []);
</script>
<script type="application/json" id="ldDesignerConfig">@json($ldDesignerConfig)</script>
@endsection
