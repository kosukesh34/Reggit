@extends('admin.layout')

@section('title', '注文詳細')
@section('page-title', '注文詳細: ' . $order->formattedOrderNumber())

@section('content')
@php
    $shopOrdersMode = (bool) ($shopOrdersMode ?? false);
    $orderStatusRoute = $shopOrdersMode ? 'admin.shop.orders.status' : 'admin.orders.status';
    $orderCompleteRoute = $shopOrdersMode ? 'admin.shop.orders.complete' : 'admin.orders.complete';
@endphp
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-info-circle"></i> 注文情報</h3>
    </div>
    <div class="order-detail-padding">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">注文番号</div>
                <div class="info-value">{{ $order->formattedOrderNumber() }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">ユーザー</div>
                <div class="info-value">
                    @if($order->user?->member !== null)
                        <a href="{{ admin_route('admin.members.detail', ['id' => $order->user->member->id]) }}">{{ admin_person_name($order->user?->name) }}</a>
                    @else
                        {{ admin_person_name($order->user?->name) }}
                    @endif
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">合計金額</div>
                <div class="info-value">¥{{ number_format($order->total_amount) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">注文種別</div>
                <div class="info-value">
                    @php
                        $orderType = $order->order_type ?? '';
                        $orderTypeLabel = \App\Enums\OrderType::getLabel($orderType) ?? $orderType;
                    @endphp
                    {{ $orderTypeLabel !== '' ? $orderTypeLabel : '—' }}
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">支払い方法</div>
                <div class="info-value">{{ $order->payment_brand_label }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">ステータス</div>
                <div class="info-value">
                    @php
                        $statusLabels = [
                            'pending' => '未処理',
                            'confirmed' => '確認済み',
                            'preparing' => '準備中',
                            'ready' => '準備完了',
                            'shipped' => '発送済み',
                            'completed' => '完了',
                            'cancelled' => 'キャンセル',
                        ];
                        $statusColors = [
                            'pending' => 'warning',
                            'confirmed' => 'info',
                            'preparing' => 'info',
                            'ready' => 'info',
                            'shipped' => 'info',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                        ];
                    @endphp
                    <span class="badge badge-{{ $statusColors[$order->status] ?? 'info' }}">
                        {{ $statusLabels[$order->status] ?? $order->status }}
                    </span>
                </div>
            </div>
            @if($order->order_type === 'shipping')
            <div class="info-item">
                <div class="info-label">配送先</div>
                <div class="info-value">
                    {{ $order->shipping_name }} / {{ $order->shipping_phone }}<br>
                    〒{{ $order->shipping_postal_code }} {{ $order->shipping_prefecture }} {{ $order->shipping_address }}<br>
                    送料: ¥{{ number_format((int) $order->shipping_fee) }}
                    @if($order->delivery_date || $order->delivery_time_slot)
                        <br><strong>お届け希望: {{ $order->delivery_date?->format('Y/m/d') ?? '日付指定なし' }} {{ $order->delivery_time_slot }}</strong>
                    @endif
                    @if($order->shipping_tracking_number)
                        @php $trackingUrl = \App\Services\Shop\ShopOperations::trackingUrl($order->shipping_carrier, $order->shipping_tracking_number); @endphp
                        <br>{{ \App\Services\Shop\ShopOperations::carrierLabel($order->shipping_carrier) }} {{ $order->shipping_tracking_number }}
                        @if($trackingUrl)<a href="{{ $trackingUrl }}" target="_blank" rel="noopener noreferrer">配送状況</a>@endif
                    @endif
                    @if($order->status !== 'pending')
                        <br><a href="{{ \Illuminate\Support\Facades\URL::signedRoute('shop.receipt', ['project_key' => $order->project?->project_key, 'order' => $order->id]) }}" target="_blank" rel="noopener noreferrer">領収書を表示</a>
                    @endif
                    @if($order->refunded_at)
                        <br><span class="badge badge-danger">返金済み ¥{{ number_format((int) $order->refund_amount) }}（{{ $order->refunded_at->format('Y/m/d H:i') }}）</span>
                    @endif
                </div>
            </div>
            @endif
            <div class="info-item">
                <div class="info-label">注文日時</div>
                <div class="info-value">{{ $order->created_at->format('Y/m/d H:i:s') }}</div>
            </div>
        </div>

        <div class="order-status-actions">
            @if($order->status !== 'completed' && $order->status !== 'cancelled')
            <form action="{{ admin_route($orderCompleteRoute, ['id' => $order->id]) }}" method="POST" class="complete-form">
                @csrf
                <button type="submit" class="btn btn-success btn-large" onclick="return confirm('この注文を完了にしますか？')">
                    <i class="fas fa-check-double"></i> 完了にする
                </button>
            </form>
            @endif

            <div class="quick-actions">
                <h4><i class="fas fa-bolt"></i> クイックアクション</h4>
                <div class="quick-action-buttons">
                    @if($order->status === 'pending')
                    <form action="{{ admin_route($orderStatusRoute, ['id' => $order->id]) }}" method="POST" class="form-inline">
                        @csrf
                        <input type="hidden" name="status" value="confirmed">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-check-circle"></i> 確認済み
                        </button>
                    </form>
                    @endif

                    @if($order->status === 'confirmed')
                    <form action="{{ admin_route($orderStatusRoute, ['id' => $order->id]) }}" method="POST" class="form-inline">
                        @csrf
                        <input type="hidden" name="status" value="preparing">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-utensils"></i> 準備中
                        </button>
                    </form>
                    @endif

                    @if($order->status === 'preparing' && $order->order_type === 'shipping')
                    <form action="{{ admin_route($orderStatusRoute, ['id' => $order->id]) }}" method="POST" class="form-inline shipping-form">
                        @csrf
                        <input type="hidden" name="status" value="shipped">
                        <label class="sr-only" for="shipping_carrier">配送業者</label>
                        <select name="shipping_carrier" id="shipping_carrier" class="form-select">
                            @foreach(\App\Services\Shop\ShopOperations::CARRIERS as $carrierKey => $carrierLabel)
                                <option value="{{ $carrierKey }}" @selected(old('shipping_carrier', $order->shipping_carrier) === $carrierKey)>{{ $carrierLabel }}</option>
                            @endforeach
                        </select>
                        <label class="sr-only" for="shipping_tracking_number">お問い合わせ番号</label>
                        <input type="text" name="shipping_tracking_number" id="shipping_tracking_number" class="form-control" maxlength="64" placeholder="お問い合わせ番号（任意）" value="{{ old('shipping_tracking_number', $order->shipping_tracking_number) }}">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-truck"></i> 発送済みにしてお客様へ連絡
                        </button>
                    </form>
                    @elseif($order->status === 'preparing')
                    <form action="{{ admin_route($orderStatusRoute, ['id' => $order->id]) }}" method="POST" class="form-inline">
                        @csrf
                        <input type="hidden" name="status" value="ready">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-check"></i> 準備完了
                        </button>
                    </form>
                    @endif

                    @if($order->status === 'ready' || $order->status === 'shipped')
                    <form action="{{ admin_route($orderCompleteRoute, ['id' => $order->id]) }}" method="POST" class="form-inline">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm('この注文を完了にしますか？')">
                            <i class="fas fa-check-double"></i> 完了
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            @if($shopOrdersMode && $order->payment_method === 'stripe' && ! in_array($order->status, ['pending', 'cancelled'], true) && $order->refunded_at === null)
            <div class="status-selector">
                <h4><i class="fas fa-undo"></i> 返金</h4>
                <p class="form-text">Stripe で全額（¥{{ number_format((int) $order->total_amount) }}）を返金し、注文をキャンセルにします。在庫と利用ポイントを戻し、獲得ポイントを取り消して、お客様へメールで知らせます。元に戻せません。</p>
                @if($order->smaregi_transaction_head_id)
                    <p class="form-text"><strong>スマレジの取引（ID: {{ $order->smaregi_transaction_head_id }}）は自動では取り消されません。返金後にスマレジで取消・返品の処理をしてください。</strong></p>
                @endif
                <form action="{{ admin_route('admin.shop.orders.refund', ['id' => $order->id]) }}" method="POST" onsubmit="return confirm('全額を返金して注文をキャンセルしますか？この操作は元に戻せません。')">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger"><i class="fas fa-undo"></i> 全額返金する</button>
                </form>
            </div>
            @endif

            <div class="status-selector">
                <h4><i class="fas fa-cog"></i> ステータス変更</h4>
                <form action="{{ admin_route($orderStatusRoute, ['id' => $order->id]) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <select name="status" class="form-select">
                            <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>未処理 (pending)</option>
                            <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>確認済み (confirmed)</option>
                            <option value="preparing" {{ $order->status === 'preparing' ? 'selected' : '' }}>準備中 (preparing)</option>
                            @if($order->order_type === 'shipping')
                                <option value="shipped" {{ $order->status === 'shipped' ? 'selected' : '' }}>発送済み (shipped)</option>
                            @else
                                <option value="ready" {{ $order->status === 'ready' ? 'selected' : '' }}>準備完了 (ready)</option>
                            @endif
                            <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>完了 (completed)</option>
                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>キャンセル (cancelled)</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-mt-10">
                            <i class="fas fa-save"></i> 更新
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> 注文明細</h3>
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
                <td>{{ $item->product->name ?? '商品情報なし' }}</td>
                <td>{{ $item->quantity }}</td>
                <td>¥{{ number_format($item->price) }}</td>
                <td>¥{{ number_format($item->price * $item->quantity) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="table-right-bold">合計:</td>
                <td class="table-bold">¥{{ number_format($order->total_amount) }}</td>
            </tr>
        </tfoot>
    </table>
</div>


@endsection
