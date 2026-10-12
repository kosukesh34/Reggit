@extends('admin.layout')

@section('title', '配信詳細')
@section('page-title', '配信詳細')
@section('page-kind', 'coupon-delivery-detail')

@section('content')
<div class="coupon-admin">
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.coupons') }}">クーポン</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.coupons.bulk-issue') }}">配布</a>
    <span class="breadcrumb-sep">/</span>
    <span>配信詳細</span>
</nav>

<div class="page-header-row">
    <div class="page-header">
        <h1 class="page-header-title"><i class="fas fa-list"></i> 配信詳細</h1>
        <p class="page-header-desc">{{ $view->couponName }}（{{ $view->couponCode }}）</p>
    </div>
    <div class="page-header-actions">

    </div>
</div>

<div class="card cp-list-card mb-4">
    <div class="card-header">
        <h3 class="card-title">配信情報</h3>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">配信種別</dt>
            <dd class="col-sm-9">{{ $view->deliveryTypeLabel }}</dd>
            <dt class="col-sm-3">状態</dt>
            <dd class="col-sm-9"><span class="badge badge-{{ $view->statusBadge }}">{{ $view->statusLabel }}</span></dd>
            <dt class="col-sm-3">日時</dt>
            <dd class="col-sm-9">{{ $view->timingLabel }}</dd>
            <dt class="col-sm-3">発行数</dt>
            <dd class="col-sm-9">{{ $view->issuedCountLabel }}</dd>
            <dt class="col-sm-3">対象の説明</dt>
            <dd class="col-sm-9">{{ $view->targetSummary }}</dd>
        </dl>
    </div>
</div>

<div class="card cp-list-card mb-4">
    <div class="card-header">
        <h3 class="card-title">対象会員</h3>
        <span class="card-header-note">{{ count($view->members) }}人</span>
    </div>
    <div class="card-body">
        @if(count($view->members) === 0)
        <div class="empty-state">
            <i class="fas fa-users"></i>
            <p>{{ $view->isEventRankUp ? 'まだ発行された会員はいません' : '対象の会員が記録されていません' }}</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>会員名</th>
                        <th>{{ \App\Support\AdminMemberIdentifier::label() }}</th>
                        <th>状態</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($view->members as $member)
                    <tr>
                        <td>
                            @if($member['member_id'] !== null)
                                <a href="{{ admin_route('admin.members.detail', ['id' => $member['member_id']]) }}">{{ $member['member_name'] }}</a>
                            @else
                                {{ $member['member_name'] }}
                            @endif
                        </td>
                        <td>{{ \App\Support\AdminMemberIdentifier::fromValues($member['member_id'] ?? $member['id'] ?? null, $member['member_number']) }}</td>
                        <td>
                            <span class="badge badge-{{ $member['is_issued'] ? 'success' : 'secondary' }}">{{ $member['status_label'] }}</span>
                        </td>
                        <td class="table-actions-cell">
                            @if($member['member_id'] !== null)
                            <div class="table-actions">
                                <a href="{{ admin_route('admin.members.detail', ['id' => $member['member_id']]) }}" class="btn btn-action btn-edit"><i class="fas fa-eye"></i> 詳細</a>
                            </div>
                            @endif
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
<link rel="stylesheet" href="{{ asset('css/admin/coupons.css') }}?v={{ config('app.asset_version') }}">
@endpush
@endsection
