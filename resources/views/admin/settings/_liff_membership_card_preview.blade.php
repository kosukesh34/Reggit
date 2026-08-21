{{-- LIFF MembershipCard と同じ構造の静的プレビュー --}}
@php
    $template = $template ?? 'classic';
    $brandName = $brandName ?? '店舗名';
    $logoText = $logoText ?? 'LOGO';
    $memberName = $memberName ?? '山田 太郎';
    $memberNumber = $memberNumber ?? '1000001234';
    $rankLabel = $rankLabel ?? 'ゴールド';
    $points = (int) ($points ?? 1234);
    $pointsLabel = number_format($points);
    $nextRankLabel = $nextRankLabel ?? '次のランクまで 766 pt';
    $cardColor = is_string($cardColor ?? null) && $cardColor !== '' ? $cardColor : '#4b5563';
    $showNextRank = ($showNextRank ?? true) === true;
    $isLight = in_array($template, ['simple', 'ticket'], true);
    $surfaceStyle = $isLight
        ? ($template === 'ticket'
            ? 'background:#fffef8;color:#171717;border-radius:14px'
            : 'background:#ffffff;color:#171717;border-radius:12px')
        : 'background:linear-gradient(135deg,'.$cardColor.',#1f2937);color:#ffffff;border-radius:34px';
    $previewClass = 'liff-membership-preview liff-membership-preview--'.$template;
    if (($scale ?? null) === 'picker') {
        $previewClass .= ' liff-membership-preview--picker';
    }
@endphp
<article
    class="{{ $previewClass }} membership-card membership-card--template-{{ $template }}{{ $isLight ? '' : ' membership-card--shadow-large' }}{{ in_array($template, ['simple', 'wallet', 'pass'], true) ? ' membership-card--align-center' : '' }}"
    data-template="{{ $template }}"
    data-card-color="{{ $cardColor }}"
    style="{{ $surfaceStyle }}"
>
    @if($template === 'simple')
        <div class="membership-card__simple-head">
            <span class="js-preview-brand">{{ $brandName }}</span>
            <span class="rank-badge js-preview-rank">{{ $rankLabel }} MEMBER</span>
        </div>
        <div class="membership-card__visuals membership-card__visuals--plain">
            <div class="barcode-mock barcode-mock--tall" aria-hidden="true"></div>
            <div class="barcode__value">{{ $memberNumber }}</div>
        </div>
        <h2 class="membership-card__name js-preview-name">{{ $memberName }}</h2>
        <div class="membership-card__simple-meta">
            <span>No. {{ $memberNumber }}</span>
            <strong class="js-preview-points">{{ $pointsLabel }} pt</strong>
        </div>
        @if($showNextRank)
            <div class="membership-card__next-rank js-preview-next-rank">{{ $nextRankLabel }}</div>
        @endif
        <div class="membership-card__meta">有効期限 2027/12/31</div>

    @elseif($template === 'ticket')
        <div class="membership-card__ticket-stub">
            <div class="membership-card__logo">{{ $logoText }}</div>
            <div class="point-summary">
                <div class="point-summary__label">ポイント</div>
                <div class="point-summary__value js-preview-points">{{ $pointsLabel }} pt</div>
            </div>
            <span class="rank-badge js-preview-rank">{{ $rankLabel }} MEMBER</span>
        </div>
        <div class="membership-card__ticket-main">
            <div class="membership-card__brand js-preview-brand">{{ $brandName }}</div>
            <h2 class="membership-card__name js-preview-name">{{ $memberName }}</h2>
            <div class="membership-card__number">No. {{ $memberNumber }}</div>
            <div class="membership-card__visuals membership-card__visuals--plain">
                <div class="barcode-mock" aria-hidden="true"></div>
            </div>
            @if($showNextRank)
                <div class="membership-card__next-rank js-preview-next-rank">{{ $nextRankLabel }}</div>
            @endif
            <div class="membership-card__meta">〜 2027/12/31</div>
        </div>

    @elseif($template === 'wallet')
        <div class="membership-card__wallet-top">
            <span class="js-preview-brand">{{ $brandName }}</span>
            <span class="rank-badge js-preview-rank">{{ $rankLabel }} MEMBER</span>
        </div>
        <h2 class="membership-card__name js-preview-name">{{ $memberName }}</h2>
        <div class="membership-card__visuals">
            <div class="barcode-mock barcode-mock--mid" aria-hidden="true"></div>
            <div class="barcode__value">{{ $memberNumber }}</div>
        </div>
        <div class="membership-card__wallet-chips">
            <span><small>POINTS</small><b class="js-preview-points">{{ $pointsLabel }}</b></span>
            <span><small>MEMBER</small><b>{{ $memberNumber }}</b></span>
        </div>
        @if($showNextRank)
            <div class="membership-card__next-rank js-preview-next-rank">{{ $nextRankLabel }}</div>
        @endif

    @else
        {{-- classic / split / barcodeFocus / pointFocus / pass / minimal --}}
        <div class="membership-card__ornament" aria-hidden="true"><i></i><i></i><i></i></div>
        <div class="membership-card__top">
            <div class="membership-card__logo">{{ $logoText }}</div>
            <div>
                <div class="membership-card__brand js-preview-brand">{{ $brandName }}</div>
                <div class="membership-card__edition">会員証 · 2026</div>
            </div>
        </div>
        <div class="membership-card__body">
            <span class="rank-badge js-preview-rank">{{ $rankLabel }} MEMBER</span>
            <span class="membership-card__member-label">会員名</span>
            <h2 class="membership-card__name js-preview-name">{{ $memberName }}</h2>
            <div class="membership-card__number">No. {{ $memberNumber }}</div>
            <div class="membership-card__meta"><span>有効期限 2027/12/31</span></div>
            @if($showNextRank)
                <div class="membership-card__next-rank js-preview-next-rank">{{ $nextRankLabel }}</div>
            @endif
        </div>
        <div class="membership-card__footer">
            <div class="point-summary">
                <div class="point-summary__label">ポイント</div>
                <div class="point-summary__value js-preview-points">{{ $pointsLabel }} pt</div>
            </div>
            <div class="membership-card__visuals">
                <div class="barcode-mock{{ $template === 'barcodeFocus' ? ' barcode-mock--tall' : ($template === 'minimal' ? ' barcode-mock--short' : '') }}" aria-hidden="true"></div>
            </div>
        </div>
    @endif
</article>
