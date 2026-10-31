@inject('memberIdentifier', 'App\Support\AdminMemberIdentifier')
@extends('admin.layout')

@section('title', '利用履歴')
@section('page-title', '利用履歴')
@section('page-kind', 'coupon-redemptions')

@section('content')
<div class="coupon-admin">
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.coupons') }}">クーポン</a>
    <span class="breadcrumb-sep">/</span>
    <span>利用履歴</span>
</nav>

<div class="page-header-row">
    <div class="page-header">
        <h1 class="page-header-title"><i class="fas fa-receipt"></i> 利用履歴</h1>
        <p class="page-header-desc">{{ $view->coupon->name ?? $view->coupon->code }}</p>
    </div>
    <div class="page-header-actions">

    </div>
</div>

<div class="card cp-list-card">
    <div class="card-header">
        <h3 class="card-title">利用者</h3>
        <span class="card-header-note">{{ $view->redemptionCount }}人</span>
    </div>
    <div class="card-body">
        @if($view->redemptionCount === 0)
        <div class="empty-state">
            <i class="fas fa-receipt"></i>
            <p>まだ利用者がいません</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>会員名</th>
                        <th>{{ $memberIdentifier->label() }}</th>
                        <th>利用日時</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($view->redemptions as $redemption)
                    <tr>
                        <td>
                            @if($redemption['member_id'] !== null)
                                <a href="{{ admin_route('admin.members.detail', ['id' => $redemption['member_id']]) }}">{{ $redemption['member_name'] }}</a>
                            @else
                                {{ $redemption['member_name'] }}
                            @endif
                        </td>
                        <td>{{ $memberIdentifier->fromValues($redemption['member_id'] ?? $redemption['id'] ?? null, $redemption['member_number']) }}</td>
                        <td>{{ $redemption['used_at'] }}</td>
                        <td class="table-actions-cell">
                            @if($redemption['member_id'] !== null)
                            <div class="table-actions">
                                <a href="{{ admin_route('admin.members.detail', ['id' => $redemption['member_id']]) }}" class="btn btn-action btn-edit"><i class="fas fa-eye"></i> 詳細</a>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $view->redemptions->links('vendor.pagination.admin') }}
        @endif
    </div>
</div>
</div>
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/coupons.css') }}?v={{ config('app.asset_version') }}">
@endpush
@endsection
