@php
    /** @var string $section */
    /** @var string $title */
    /** @var string $icon */
    /** @var string $placeholder */
    /** @var string $queryKey */
    /** @var string $fromKey */
    /** @var string $toKey */
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $items */
    /** @var array<string, string> $historyFilters */
    $keyword = $historyFilters[$queryKey] ?? '';
    $from = $historyFilters[$fromKey] ?? '';
    $to = $historyFilters[$toKey] ?? '';
    $isSearchOpen = $keyword !== '' || $from !== '' || $to !== '';
@endphp
<div
    class="card member-history-panel{{ $isSearchOpen ? ' is-search-open' : '' }}"
    data-section="{{ $section }}"
    data-query-key="{{ $queryKey }}"
    data-from-key="{{ $fromKey }}"
    data-to-key="{{ $toKey }}"
    data-history-url="{{ admin_route('admin.members.history', ['id' => $member->id, 'section' => $section]) }}"
>
    <div class="card-header card-header-with-action">
        <h3 class="card-title">
            <i class="{{ $icon }}"></i> {{ $title }}
            <span class="card-header-note">{{ $items->total() }}件</span>
        </h3>
        <div class="member-history-actions">
            <form
                id="member-history-search-{{ $section }}"
                class="member-history-search"
                data-history-form
                @if(! $isSearchOpen) hidden @endif
            >
                <input
                    type="search"
                    class="form-control form-control-sm member-history-keyword"
                    placeholder="{{ $placeholder }}"
                    value="{{ $keyword }}"
                    autocomplete="off"
                    data-history-input
                >
                <input
                    type="date"
                    class="form-control form-control-sm member-history-date"
                    value="{{ $from }}"
                    data-history-from
                    aria-label="開始日"
                >
                <span class="member-history-date-sep" aria-hidden="true">〜</span>
                <input
                    type="date"
                    class="form-control form-control-sm member-history-date"
                    value="{{ $to }}"
                    data-history-to
                    aria-label="終了日"
                >
                <button type="submit" class="btn btn-sm btn-primary member-history-submit no-loading" title="検索実行">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            <button
                type="button"
                class="btn btn-sm btn-secondary member-history-toggle no-loading"
                data-history-toggle
                aria-expanded="{{ $isSearchOpen ? 'true' : 'false' }}"
                aria-controls="member-history-search-{{ $section }}"
                aria-label="{{ $isSearchOpen ? '検索を閉じる' : '検索を開く' }}"
                title="{{ $isSearchOpen ? '検索を閉じる' : '検索' }}"
            >
                <i class="fas {{ $isSearchOpen ? 'fa-times' : 'fa-search' }}" aria-hidden="true"></i>
            </button>
        </div>
    </div>
    @if($section === 'stamps' && $member->memberStamps->isNotEmpty())
    <div class="card-body pb-0 member-history-stamp-status">
        <p class="text-muted small mb-2">現在の状況:
            @foreach($member->memberStamps as $ms)
                {{ $ms->stampCard->name ?? '—' }} {{ $ms->current_stamps }} / {{ $ms->stampCard->required_stamps ?? 0 }}{{ $ms->completed_at ? '（達成済み）' : '' }}@if(!$loop->last)、@endif
            @endforeach
        </p>
    </div>
    @endif
    <div class="member-history-body" data-history-body>
        @include('admin.partials.member-history.'.$section, [
            'member' => $member,
            'items' => $items,
            'paginationQuery' => array_filter([
                $queryKey => $keyword,
                $fromKey => $from,
                $toKey => $to,
            ], static fn ($v) => $v !== ''),
        ])
    </div>
</div>
