@extends('admin.layout')

@section('title', '取引詳細')
@section('page-title', '取引詳細')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.members') }}">会員一覧</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.members.detail', ['id' => $member->id]) }}">{{ $member->user->name ?? ('会員 '.\App\Support\AdminMemberIdentifier::value($member)) }}</a>
    <span class="breadcrumb-sep">/</span>
    <span>取引詳細</span>
</nav>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-info-circle"></i> 取引情報</h3>
    </div>
    <div class="order-detail-padding">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">会員</div>
                <div class="info-value">
                    <a href="{{ admin_route('admin.members.detail', ['id' => $member->id]) }}">
                        {{ $member->user->name ?? ('会員 '.\App\Support\AdminMemberIdentifier::value($member)) }}
                    </a>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">来店日時</div>
                <div class="info-value">{{ $visit->visited_at->format('Y/m/d H:i:s') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">経路</div>
                <div class="info-value">{{ \App\Enums\MemberVisitSource::getLabel($visit->source) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">購入額</div>
                <div class="info-value">
                    @if($visit->amount !== null)
                        ¥{{ number_format($visit->amount) }}
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">スマレジ取引ID</div>
                <div class="info-value">{{ $visit->smaregi_transaction_head_id ?? '—' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">メモ</div>
                <div class="info-value">{{ $visit->note ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> 購入商品</h3>
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
            @forelse($visit->items as $item)
            <tr>
                <td>{{ $item->product->name ?? $item->product_name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>¥{{ number_format($item->unit_price) }}</td>
                <td>¥{{ number_format($item->amount) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <p>購入商品の明細がありません</p>
                </td>
            </tr>
            @endforelse
        </tbody>
        @if($visit->items->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="3" class="table-right-bold">合計:</td>
                <td class="table-bold">
                    ¥{{ number_format($visit->amount ?? $visit->items->sum('amount')) }}
                </td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

<div class="form-actions">
    <a href="{{ admin_route('admin.members.detail', ['id' => $member->id]) }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> 会員詳細へ戻る
    </a>
</div>
@endsection
