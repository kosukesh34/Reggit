@extends('admin.layout')

@section('title', 'スケジュール配信')
@section('page-title', 'スケジュール配信')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.announcements') }}">お知らせ</a>
    <span class="breadcrumb-sep">/</span>
    <span>スケジュール配信</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-clock"></i> スケジュール配信</h1>
</div>

<div class="card">
    <div class="card-header card-header-with-action">
        <h2 class="card-title">一覧</h2>
        <div class="table-actions">
            @if($deliveries->contains(fn ($d) => $d->isWeatherRain()))
                <a href="{{ admin_route('admin.weather-coupons.index') }}" class="btn btn-secondary"><i class="fas fa-cloud-rain"></i> 雨の日の判定結果</a>
            @endif
            <a href="{{ admin_route('admin.after-registration-deliveries.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> 追加</a>
        </div>
    </div>
    <div class="card-body">
        @if($deliveries->isEmpty())
        <div class="empty-state">
            <i class="fas fa-clock"></i>
            <p>スケジュール配信がありません</p>
            <p class="empty-state-hint">「追加」から、条件達成配信や予約配信を設定できます</p>
        </div>
        @else
        <table class="table">
            <thead>
                <tr>
                    <th>種類</th>
                    <th>条件</th>
                    <th>内容</th>
                    <th>クーポン</th>
                    <th>状態</th>
                    <th class="table-actions-cell">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deliveries as $d)
                <tr>
                    <td>{{ \App\Enums\ScheduledDeliveryCategory::getLabel((string) $d->category) }}</td>
                    <td>{{ $d->detailLabel() }}</td>
                    <td>
                        @if($d->isCouponOnly())
                            クーポンのみ{{ $d->send_notification ? '（通知あり）' : '（通知なし）' }}
                        @else
                            {{ \Illuminate\Support\Str::limit((string) $d->title, 40) }}
                        @endif
                    </td>
                    <td>{{ $d->coupon ? $d->coupon->name : '—' }}</td>
                    <td>@if($d->is_active)<span class="badge badge-success">有効</span>@else<span class="badge badge-secondary">無効</span>@endif</td>
                    <td class="table-actions-cell">
                        <div class="table-actions">
                            <a href="{{ admin_route('admin.after-registration-deliveries.edit', ['id' => $d->id]) }}" class="btn btn-action btn-edit"><i class="fas fa-edit"></i> 編集</a>
                            <form action="{{ admin_route('admin.after-registration-deliveries.destroy', ['id' => $d->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('削除しますか？');">@csrf @method('DELETE')<button type="submit" class="btn btn-action btn-danger"><i class="fas fa-trash"></i> 削除</button></form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
