@extends('admin.layout')

@section('title', '設定')
@section('page-title', '設定')

@section('content')
@php
    $settingsPlan = isset($currentProject) && $currentProject !== null
        ? (string) ($currentProject->plan ?? \App\Enums\ProjectPlan::NORMAL)
        : \App\Enums\ProjectPlan::NORMAL;
    $settingsSections = \App\Support\AdminSettingsNavigation::sections(auth('admin')->user(), $settingsPlan);
@endphp

<div class="settings-hub" data-settings-hub>
    <div class="settings-hub-toolbar">
        <label class="settings-hub-search-wrap">
            <i class="fas fa-search" aria-hidden="true"></i>
            <span class="sr-only">設定を検索</span>
            <input type="search" class="settings-hub-search" placeholder="設定を検索" autocomplete="off" data-settings-search>
        </label>
    </div>

    <div class="settings-hub-layout">
        <div class="settings-hub-sections">
            @foreach($settingsSections as $index => $section)
                <section class="settings-hub-section" id="settings-section-{{ $index }}" data-settings-section>
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
document.querySelector('[data-settings-search]')?.addEventListener('input', function () {
    const query = this.value.trim().normalize('NFKC').toLocaleLowerCase('ja');
    let visibleCount = 0;
    document.querySelectorAll('[data-settings-section]').forEach(function (section) {
        let sectionCount = 0;
        section.querySelectorAll('[data-settings-item]').forEach(function (item) {
            const matches = item.dataset.search.normalize('NFKC').toLocaleLowerCase('ja').includes(query);
            item.hidden = !matches;
            sectionCount += Number(matches);
        });
        section.hidden = sectionCount === 0;
        visibleCount += sectionCount;
    });
    document.querySelector('[data-settings-empty]').hidden = visibleCount !== 0;
});
</script>
@endpush
