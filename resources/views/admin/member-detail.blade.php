@extends('admin.layout')

@section('title', '会員詳細')
@section('page-title', '会員詳細: ' . $member->member_number)

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">会員情報</h3>
    </div>
    <div style="padding: 20px;">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
            <div>
                <strong>会員番号:</strong> {{ $member->member_number }}
            </div>
            <div>
                <strong>ユーザー名:</strong> {{ $member->user->name ?? 'N/A' }}
            </div>
            <div>
                <strong>現在のポイント:</strong> {{ number_format($member->points) }}pt
            </div>
            <div>
                <strong>ステータス:</strong> 
                <span class="badge badge-{{ $member->status === 'active' ? 'success' : 'danger' }}">
                    {{ $member->status === 'active' ? '有効' : '無効' }}
                </span>
            </div>
            <div>
                <strong>登録日:</strong> {{ $member->created_at->format('Y/m/d H:i:s') }}
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">ポイント履歴</h3>
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
                <td colspan="5" style="text-align: center; padding: 40px;">ポイント履歴がありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 20px;">
    <a href="{{ route('admin.members') }}" class="btn btn-secondary">戻る</a>
</div>
@endsection


