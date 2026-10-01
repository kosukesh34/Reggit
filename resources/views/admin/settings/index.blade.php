@extends('admin.layout')

@section('title', '店舗設定')
@section('page-title', '店舗設定')

@section('content')
@php
    $settingsPlan = isset($currentProject) && $currentProject !== null
        ? (string) ($currentProject->plan ?? \App\Enums\ProjectPlan::NORMAL)
        : \App\Enums\ProjectPlan::NORMAL;
    $settingsSections = \App\Support\AdminSettingsNavigation::sections(auth('admin')->user(), $settingsPlan);
@endphp

<div class="settings-hub" data-settings-hub>
    <div class="settings-overview"><span class="settings-eyebrow">STORE SETTINGS</span><h1>店舗設定</h1><p>店舗の運用からお客様向けアプリまで、目的に合わせて設定できます。</p></div>
    <div class="settings-hub-toolbar">
        <label class="settings-hub-search-wrap">
            <i class="fas fa-search" aria-hidden="true"></i>
            <span class="sr-only">設定を検索</span>
            <input type="search" class="settings-hub-search" placeholder="例：ロゴ、営業時間、LINE" autocomplete="off" data-settings-search>
        </label>
    </div>

    <div class="settings-category-tabs" role="tablist" aria-label="設定カテゴリ" hidden data-settings-tabs>
        <button type="button" role="tab" id="settings-tab-all" aria-selected="true" aria-controls="settings-panels" data-settings-tab="all">すべて</button>
        @foreach($settingsSections as $index => $section)
            <button type="button" role="tab" id="settings-tab-{{ $index }}" aria-selected="false" tabindex="-1" aria-controls="settings-panels" data-settings-tab="{{ $index }}">{{ $section['title'] }}</button>
        @endforeach
    </div>
    <div class="settings-hub-layout" id="settings-panels" role="tabpanel" aria-labelledby="settings-tab-all">
        <div class="settings-hub-sections">
            @foreach($settingsSections as $index => $section)
                <section class="settings-hub-section" id="settings-section-{{ $index }}" data-settings-section="{{ $index }}">
                    <h2 class="settings-hub-heading">{{ $section['title'] }}</h2>
                    <div class="settings-hub-grid">
                        @foreach($section['items'] as $item)
                            <a href="{{ admin_route($item['route']) }}" class="settings-hub-card" data-settings-item data-search="{{ $section['title'] }} {{ $item['label'] }} {{ $item['hint'] }}">
                                <span class="settings-hub-icon" aria-hidden="true"><i class="{{ $item['icon'] }}"></i></span>
                                <span class="settings-hub-copy"><strong>{{ $item['label'] }}</strong><span>{{ $item['hint'] }}</span></span>
                                <i class="fas fa-chevron-right settings-hub-arrow" aria-hidden="true"></i>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
            <p class="settings-hub-empty" data-settings-empty hidden>該当する設定はありません。</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const settingsTabs = Array.from(document.querySelectorAll('[data-settings-tab]'));
let activeCategory = 'all';
function filterSettings() {
    const query = document.querySelector('[data-settings-search]').value.trim().normalize('NFKC').toLocaleLowerCase('ja');
    let visibleCount = 0;
    document.querySelectorAll('[data-settings-section]').forEach(function (section) {
        let sectionCount = 0;
        section.querySelectorAll('[data-settings-item]').forEach(function (item) {
            const matches = (query !== '' || activeCategory === 'all' || section.dataset.settingsSection === activeCategory) && item.dataset.search.normalize('NFKC').toLocaleLowerCase('ja').includes(query);
            item.hidden = !matches;
            sectionCount += Number(matches);
        });
        section.hidden = sectionCount === 0;
        visibleCount += sectionCount;
    });
    document.querySelector('[data-settings-empty]').hidden = visibleCount !== 0;
}
settingsTabs.forEach(function (tab, index) {
    tab.addEventListener('click', function () {
        activeCategory = tab.dataset.settingsTab;
        document.querySelector('[data-settings-search]').value = '';
        settingsTabs.forEach(button => {
            button.setAttribute('aria-selected', String(button === tab));
            button.tabIndex = button === tab ? 0 : -1;
        });
        document.querySelector('#settings-panels').setAttribute('aria-labelledby', tab.id);
        filterSettings();
    });
    tab.addEventListener('keydown', function (event) {
        let next;
        if (event.key === 'ArrowRight') next = (index + 1) % settingsTabs.length;
        if (event.key === 'ArrowLeft') next = (index - 1 + settingsTabs.length) % settingsTabs.length;
        if (event.key === 'Home') next = 0;
        if (event.key === 'End') next = settingsTabs.length - 1;
        if (next !== undefined) { event.preventDefault(); settingsTabs[next].click(); settingsTabs[next].focus(); }
    });
});
document.querySelector('[data-settings-tabs]').hidden = false;
document.querySelector('[data-settings-search]').addEventListener('input', filterSettings);
</script>
@endpush
