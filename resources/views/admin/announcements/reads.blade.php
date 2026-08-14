@extends('admin.layout')

@section('title', '配信結果')
@section('page-title', '配信結果')
@section('page-kind', 'announcement-results')

@section('content')
@php
    $announcement = $view->announcement;
    $isScheduled = $announcement->delivery_status === \App\Models\Announcement::DELIVERY_STATUS_SCHEDULED;
    $timingLabel = ($announcement->delivery_timing ?? null) === \App\Models\Announcement::DELIVERY_TIMING_SCHEDULED || $isScheduled
        ? '予約配信'
        : '即時配信';
    $audienceLabel = match ($announcement->delivery_type) {
        \App\Models\Announcement::DELIVERY_TYPE_PERSONAL => '個別指定',
        \App\Models\Announcement::DELIVERY_TYPE_SEGMENT => '条件指定',
        \App\Models\Announcement::DELIVERY_TYPE_AFTER_REGISTRATION => 'スケジュール配信',
        default => '全会員',
    };
@endphp

<div class="announcement-results">
    <nav class="breadcrumb" aria-label="パンくず">
        <a href="{{ admin_route('admin.announcements') }}">お知らせ</a>
        <span class="breadcrumb-sep">/</span>
        <span>配信結果</span>
    </nav>

    <div class="page-header-row">
        <div class="page-header">
            <h1 class="page-header-title"><i class="fas fa-chart-bar"></i> 配信結果</h1>
            <p class="page-header-desc">{{ $announcement->title }}</p>
            <div class="ar-meta">
                <span><i class="far fa-clock"></i> {{ $timingLabel }}</span>
                <span><i class="fas fa-users"></i> {{ $audienceLabel }}</span>
                @if($isScheduled && $announcement->scheduled_at)
                    <span><i class="far fa-calendar"></i> {{ $announcement->scheduled_at->format('Y/m/d H:i') }} 配信予定</span>
                @elseif($announcement->sent_at)
                    <span><i class="fas fa-paper-plane"></i> {{ $announcement->sent_at->format('Y/m/d H:i') }} 配信</span>
                @endif
            </div>
        </div>
        <div class="page-header-actions">
            <a href="{{ admin_route('admin.announcements') }}" class="btn btn-secondary">一覧へ戻る</a>
        </div>
    </div>

    <div class="ar-summary" aria-label="配信状況サマリー">
        <div class="ar-summary-card">
            <span class="ar-summary-icon ar-summary-icon--blue"><i class="fas fa-users"></i></span>
            <div><small>配信対象</small><strong>{{ number_format($view->targetCount) }}人</strong></div>
        </div>
        <div class="ar-summary-card">
            <span class="ar-summary-icon ar-summary-icon--green"><i class="fas fa-envelope-open"></i></span>
            <div><small>既読</small><strong>{{ number_format($view->readCount) }}人</strong><span>{{ $view->targetCount > 0 ? number_format(($view->readCount / $view->targetCount) * 100, 1) : '0.0' }}%</span></div>
        </div>
        @if($announcement->delivery_coupon_id !== null)
            <div class="ar-summary-card">
                <span class="ar-summary-icon ar-summary-icon--orange"><i class="fas fa-ticket-alt"></i></span>
                <div><small>クーポン配布</small><strong>{{ number_format($view->couponIssuedCount) }}人</strong></div>
            </div>
            <div class="ar-summary-card">
                <span class="ar-summary-icon ar-summary-icon--violet"><i class="fas fa-check-circle"></i></span>
                <div><small>クーポン利用</small><strong>{{ number_format($view->couponUsedCount) }}人</strong><span>{{ $view->couponIssuedCount > 0 ? number_format(($view->couponUsedCount / $view->couponIssuedCount) * 100, 1) : '0.0' }}%</span></div>
            </div>
        @endif
    </div>

    <div class="card ar-recipient-card">
        <div class="card-header">
            <div>
                <h3 class="card-title">{{ $isScheduled ? '配信予定者' : '配信対象者' }}</h3>
                <p class="ar-card-help">
                    @if($isScheduled && $announcement->shouldResolveAudienceAtSend())
                        配信時刻に条件へ一致する会員へ送ります。いま条件に合う会員は下記の予定です。
                    @else
                        配信時点で保存した対象者と、その後の既読・クーポン利用状況です。
                    @endif
                </p>
            </div>
            <span class="card-header-note">{{ number_format($view->targetCount) }}人</span>
        </div>
        <div class="card-body">
            @if($view->targetCount === 0)
                <div class="empty-state">
                    <i class="fas fa-user-slash"></i>
                    <p>配信対象者はいません</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table ar-recipient-table">
                        <thead>
                            <tr>
                                <th>会員</th>
                                <th>会員番号</th>
                                <th>配信チャネル</th>
                                <th>既読</th>
                                @if($announcement->delivery_coupon_id !== null)<th>クーポン</th>@endif
                                <th class="table-actions-cell">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($view->recipients as $recipient)
                                <tr>
                                    <td><a href="{{ admin_route('admin.members.detail', ['id' => $recipient['member_id']]) }}"><strong>{{ $recipient['member_name'] }}</strong></a></td>
                                    <td>{{ $recipient['member_number'] }}</td>
                                    <td>{{ $recipient['channel'] }}</td>
                                    <td>
                                        @if($recipient['read_at'])
                                            <span class="badge badge-success">既読</span>
                                            <small class="ar-status-date">{{ $recipient['read_at'] }}</small>
                                        @else
                                            <span class="badge badge-secondary">未読</span>
                                        @endif
                                    </td>
                                    @if($announcement->delivery_coupon_id !== null)
                                        <td>
                                            @if($recipient['coupon_status'] === 'used')
                                                <span class="badge badge-success">利用済み</span>
                                                <small class="ar-status-date">{{ $recipient['coupon_used_at'] }}</small>
                                            @elseif($recipient['coupon_status'] === 'unused')
                                                <span class="badge badge-info">未利用</span>
                                            @else
                                                <span class="badge badge-secondary">未配布</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="table-actions-cell">
                                        <a href="{{ admin_route('admin.members.detail', ['id' => $recipient['member_id']]) }}" class="btn btn-action btn-edit"><i class="fas fa-eye"></i> 会員詳細</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/announcement-delivery.css') }}?v={{ (int) @filemtime(public_path('css/admin/announcement-delivery.css')) }}">
@endpush
@endsection
