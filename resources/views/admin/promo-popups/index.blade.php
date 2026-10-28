@extends('admin.layout')
@section('title', 'ポップアップ')
@section('page-title', 'ポップアップ')

@php
    use App\Domain\Promotion\PromoPopupAudience;
    use App\Models\PromoPopup;

    $typeIcons = ['fullscreen' => 'fa-expand', 'modal' => 'fa-window-restore', 'bottom_sheet' => 'fa-arrow-up-from-bracket', 'banner' => 'fa-grip-lines'];
@endphp

@section('page-header-actions')
    <div class="eg-header-actions">
        <a href="{{ admin_route('admin.promo-popups.create') }}" class="btn btn-primary"><i class="fas fa-plus" aria-hidden="true"></i> ポップアップを作成</a>
    </div>
@endsection

@section('content')
@include('admin.engagement._assets')
<div class="eg-page">
<p class="eg-lead">アプリ（LINE・iOS・Android）を開いたときに宣伝を表示します。表示中のものが複数あるときは、優先度の高いものから1つだけ出します。タップ率は、表示された回数に対してボタンが押された割合です。</p>

<section class="eg-card">
    @if($popups->isEmpty())
        <div class="eg-empty">
            <i class="fas fa-window-maximize" aria-hidden="true"></i>
            <h3>まだポップアップがありません</h3>
            <p>キャンペーンや新メニューを、アプリを開いた瞬間にお知らせできます。</p>
            <a href="{{ admin_route('admin.promo-popups.create') }}" class="btn btn-primary">ポップアップを作成</a>
        </div>
    @else
        <div class="eg-table-wrap">
        <table class="eg-table">
            <thead>
                <tr><th>ポップアップ</th><th>状態</th><th>相手</th><th>表示期間</th><th>表示 / タップ</th><th class="table-actions-cell"><span class="sr-only">操作</span></th></tr>
            </thead>
            <tbody>
                @foreach($popups as $popup)
                @php
                    $showing = $popup->is_active && ($popup->starts_at === null || $popup->starts_at->lte(now())) && ($popup->ends_at === null || $popup->ends_at->gt(now()));
                    $taps = (int) $popup->clicks_total + (int) $popup->secondary_clicks_total;
                @endphp
                <tr>
                    <td>
                        <div class="eg-media">
                            @if($popup->image_url)
                            <img src="{{ $popup->image_url }}" alt="" class="eg-thumb">
                            @else
                            <span class="eg-thumb eg-thumb--empty"><i class="fas {{ $typeIcons[$popup->display_type] ?? 'fa-window-maximize' }}" aria-hidden="true"></i></span>
                            @endif
                            <div>
                                <a href="{{ admin_route('admin.promo-popups.edit', ['id' => $popup->id]) }}" class="eg-table-title">{{ $popup->title }}</a>
                                <span class="eg-table-sub">{{ PromoPopup::DISPLAY_TYPES[$popup->display_type] ?? $popup->display_type }} · {{ PromoPopup::FREQUENCIES[$popup->frequency] ?? $popup->frequency }} · 優先度 {{ $popup->sort_order }}</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="eg-badge {{ $showing ? 'is-on' : '' }}">{{ $showing ? '表示中' : ($popup->is_active ? '期間外' : '停止中') }}</span></td>
                    <td>{{ PromoPopupAudience::getLabel($popup->audience) }}{{ $popup->store_ids !== null ? '（店舗指定）' : '' }}</td>
                    <td class="eg-muted eg-nowrap">{{ $popup->starts_at?->format('Y/m/d H:i') ?? '指定なし' }}<br>〜 {{ $popup->ends_at?->format('Y/m/d H:i') ?? '指定なし' }}</td>
                    <td>
                        <div class="eg-rate">
                            <span>{{ number_format((int) $popup->views_total) }} / {{ number_format($taps) }}{{ $popup->tapRate() !== null ? '（'.$popup->tapRate().'%）' : '' }}</span>
                            <span class="eg-rate-track" aria-hidden="true"><span class="eg-rate-fill" style="width: {{ min(100, $popup->tapRate() ?? 0) }}%"></span></span>
                        </div>
                    </td>
                    <td>
                        <div class="eg-actions">
                            <a href="{{ admin_route('admin.promo-popups.edit', ['id' => $popup->id]) }}" class="eg-icon-button" aria-label="{{ $popup->title }}を編集"><i class="fas fa-pen" aria-hidden="true"></i></a>
                            <form action="{{ admin_route('admin.promo-popups.duplicate', ['id' => $popup->id]) }}" method="POST">
                                @csrf
                                <button type="submit" class="eg-icon-button" aria-label="{{ $popup->title }}を複製"><i class="fas fa-copy" aria-hidden="true"></i></button>
                            </form>
                            <form action="{{ admin_route('admin.promo-popups.destroy', ['id' => $popup->id]) }}" method="POST" class="delete-form" data-item-name="ポップアップ「{{ $popup->title }}」">
                                @csrf @method('DELETE')
                                <button type="submit" class="eg-icon-button is-danger" aria-label="{{ $popup->title }}を削除"><i class="fas fa-trash" aria-hidden="true"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @endif
</section>
</div>
@endsection
