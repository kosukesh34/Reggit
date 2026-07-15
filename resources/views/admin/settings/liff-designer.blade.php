@extends('admin.layout')

@section('title', 'LIFFデザイン')
@section('page-title', 'LIFFデザイン')
@section('page-kind', 'liff-designer')

@push('styles-before-vite')
<link rel="stylesheet" href="{{ asset('css/admin/liff-designer.css') }}?v=20260715e">
@endpush

@section('content')
<form action="{{ admin_route('admin.settings.liff-designer.update') }}" method="POST" id="liffDesignerForm" hidden>
    @csrf
    <input type="hidden" name="layouts_json" id="layoutsJson">
    <input type="hidden" name="custom_css" id="customCssInput" value="{{ $customCss ?? '' }}">
    <input type="hidden" name="tab_settings_json" id="tabSettingsJson">
    <input type="hidden" name="menu_items_json" id="menuItemsJson">
</form>

<div id="liff-designer-root"></div>

@php
    $ldDesignerConfig = [
        'layouts' => $layouts ?? [],
        'tabSettings' => $tabSettings ?? [],
        'templates' => $templates ?? [],
        'customCss' => $customCss ?? '',
        'featureFlags' => $featureFlags ?? [],
        'menuItems' => $menuItems ?? [],
        'themePresets' => \App\Support\LiffThemePresets::all(),
        'fontStacks' => \App\Support\LiffThemePresets::fontStacks(),
        'surfaceTones' => \App\Support\LiffThemePresets::surfaceTones(),
        'contentWidths' => collect(\App\Support\LiffThemePresets::contentWidths())->map(fn ($w) => ['label' => $w['label']])->all(),
        'backgroundStyles' => \App\Support\LiffThemePresets::backgroundStyles(),
        'formAction' => admin_route('admin.settings.liff-designer.update'),
        'csrfToken' => csrf_token(),
        'backUrl' => admin_route('admin.settings.basic'),
        'lineFriendAddUrl' => old('line_friend_add_url', $lineFriendAddUrl ?? ''),
    ];
@endphp
<script type="application/json" id="ldDesignerConfig">@json($ldDesignerConfig)</script>
@endsection
