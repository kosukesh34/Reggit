@php
    $currentMemberCardDesign = old('member_card_design', $view->memberCardDesign ?? 'default');
    $memberCardDesignOptions = [
        'default' => ['label' => 'スタンダード', 'desc' => 'クラシックな縦型会員証'],
        'compact' => ['label' => 'シンプル', 'desc' => '白地・バーコード中心'],
        'ticket' => ['label' => 'チケット', 'desc' => '半券付きクーポン風'],
        'minimal_line' => ['label' => 'ミニマル', 'desc' => '装飾を抑えた構成'],
        'split_banner' => ['label' => 'スプリット', 'desc' => 'ポイントとコードを分離'],
        'logo_header' => ['label' => 'レジ提示', 'desc' => 'バーコードを大きく'],
        'logo_top' => ['label' => 'ポイント', 'desc' => 'ポイント残高を主役に'],
        'wallet' => ['label' => 'ウォレット', 'desc' => 'スマホウォレット風'],
        'emblem' => ['label' => 'デジタルパス', 'desc' => '中央配置の会員パス'],
        'logo_center' => ['label' => 'ロゴ中央', 'desc' => 'パス型・ロゴ強調'],
    ];
@endphp
<div class="ranks-design-picker" role="radiogroup" aria-label="会員証デザイン">
    @foreach($memberCardDesignOptions as $designKey => $designMeta)
    @php $template = \App\Support\MemberCardDesignMap::templateForDesign($designKey) ?? 'classic'; @endphp
    <label class="ranks-design-option{{ $currentMemberCardDesign === $designKey ? ' is-selected' : '' }}">
        <input
            type="radio"
            name="member_card_design"
            value="{{ $designKey }}"
            data-template="{{ $template }}"
            {{ $currentMemberCardDesign === $designKey ? 'checked' : '' }}
        >
        <span class="ranks-design-option__preview" aria-hidden="true">
            @include('admin.settings._liff_membership_card_preview', [
                'template' => $template,
                'rankLabel' => 'ゴールド',
                'cardColor' => '#4b5563',
                'points' => 1200,
                'scale' => 'picker',
                'showNextRank' => false,
            ])
        </span>
        <span class="ranks-design-option__copy">
            <strong>{{ $designMeta['label'] }}</strong>
            <small>{{ $designMeta['desc'] }}</small>
        </span>
    </label>
    @endforeach
</div>
