@extends('admin.layout')

@section('title', '注文詳細')
@section('page-title', '注文詳細: ' . $order->order_number)

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">注文情報</h3>
    </div>
    <div style="padding: 20px;">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
            <div>
                <strong>注文番号:</strong> {{ $order->order_number }}
            </div>
            <div>
                <strong>ユーザー:</strong> {{ $order->user->name ?? 'N/A' }}
            </div>
            <div>
                <strong>合計金額:</strong> ¥{{ number_format($order->total_amount) }}
            </div>
            <div>
                <strong>支払い方法:</strong> {{ $order->payment_method ?? 'N/A' }}
            </div>
            <div>
                <strong>ステータス:</strong> 
                <span class="badge badge-{{ 
                    $order->status === 'completed' ? 'success' : 
                    ($order->status === 'pending' ? 'warning' : 
                    ($order->status === 'cancelled' ? 'danger' : 'info')) 
                }}">
                    {{ $order->status }}
                </span>
            </div>
            <div>
                <strong>注文日時:</strong> {{ $order->created_at->format('Y/m/d H:i:s') }}
            </div>
        </div>

        <form action="{{ route('admin.orders.status', $order->id) }}" method="POST" style="margin-bottom: 20px;">
            @csrf
            <div style="display: flex; gap: 10px; align-items: center;">
                <label class="form-label" style="margin: 0;">ステータス変更:</label>
                <select name="status" class="form-select" style="width: auto;">
                    <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>pending</option>
                    <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>confirmed</option>
                    <option value="preparing" {{ $order->status === 'preparing' ? 'selected' : '' }}>preparing</option>
                    <option value="ready" {{ $order->status === 'ready' ? 'selected' : '' }}>ready</option>
                    <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>completed</option>
                    <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>cancelled</option>
                </select>
                <button type="submit" class="btn btn-primary">更新</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">注文明細</h3>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>商品名</th>
                <th>数量</th>
                <th>単価</th>
                <th>小計</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product->name ?? 'N/A' }}</td>
                <td>{{ $item->quantity }}</td>
                <td>¥{{ number_format($item->price) }}</td>
                <td>¥{{ number_format($item->price * $item->quantity) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: right; font-weight: 700;">合計:</td>
                <td style="font-weight: 700;">¥{{ number_format($order->total_amount) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

<div style="margin-top: 20px;">
    <a href="{{ route('admin.orders') }}" class="btn btn-secondary">戻る</a>
</div>
@endsection

