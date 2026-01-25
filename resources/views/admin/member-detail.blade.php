@extends('admin.layout')

@section('title', '会員詳細')
@section('page-title', '会員詳細: ' . $member->member_number)

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user"></i> 会員情報</h3>
    </div>
    <div style="padding: 24px;">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">会員番号</div>
                <div class="info-value">{{ $member->member_number }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">ユーザー名</div>
                <div class="info-value">{{ $member->user->name ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">現在のポイント</div>
                <div class="info-value">{{ number_format($member->points) }}pt</div>
            </div>
            <div class="info-item">
                <div class="info-label">ステータス</div>
                <div class="info-value">
                    <span class="badge badge-{{ $member->status === 'active' ? 'success' : 'danger' }}">
                        {{ $member->status === 'active' ? '有効' : '無効' }}
                    </span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">登録日</div>
                <div class="info-value">{{ $member->created_at->format('Y/m/d H:i:s') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-coins"></i> ポイント履歴</h3>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>日時</th>
                <th>種類</th>
                <th>ポイント</th>
                <th>説明</th>
                <th>注文番号</th>
            </tr>
        </thead>
        <tbody>
            @forelse($member->pointTransactions as $transaction)
            <tr>
                <td>{{ $transaction->created_at->format('Y/m/d H:i') }}</td>
                <td>
                    <span class="badge badge-{{ $transaction->type === 'earned' ? 'success' : 'danger' }}">
                        {{ $transaction->type === 'earned' ? '獲得' : '使用' }}
                    </span>
                </td>
                <td>{{ $transaction->points > 0 ? '+' : '' }}{{ number_format($transaction->points) }}pt</td>
                <td>{{ $transaction->description ?? 'N/A' }}</td>
                <td>{{ $transaction->order->order_number ?? 'N/A' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="empty-state">
                    <i class="fas fa-coins"></i>
                    <p>ポイント履歴がありません</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="form-actions">
    <a href="{{ route('admin.members') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> 戻る
    </a>
</div>
@endsection


