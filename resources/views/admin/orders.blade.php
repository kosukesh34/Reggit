@extends('admin.layout')

@section('title', '注文管理')
@section('page-title', '注文管理')

@section('content')
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>注文番号</th>
                <th>ユーザー</th>
                <th>金額</th>
                <th>支払い方法</th>
                <th>ステータス</th>
                <th>日時</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
            <tr>
                <td><a href="{{ route('admin.orders.detail', $order->id) }}">{{ $order->order_number }}</a></td>
                <td>{{ $order->user->name ?? 'N/A' }}</td>
                <td>¥{{ number_format($order->total_amount) }}</td>
                <td>{{ $order->payment_method ?? 'N/A' }}</td>
                <td>
                    <span class="badge badge-{{ 
                        $order->status === 'completed' ? 'success' : 
                        ($order->status === 'pending' ? 'warning' : 
                        ($order->status === 'cancelled' ? 'danger' : 'info')) 
                    }}">
                        {{ $order->status }}
                    </span>
                </td>
                <td>{{ $order->created_at->format('Y/m/d H:i') }}</td>
                <td>
                    <a href="{{ route('admin.orders.detail', $order->id) }}" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;">詳細</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 40px;">注文がありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination">
        {{ $orders->links() }}
    </div>
</div>
@endsection

