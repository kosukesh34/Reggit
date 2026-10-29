@extends('admin.layout')

@section('title', 'LINE未送信の履歴')
@section('page-title', 'LINE未送信の履歴')

@section('content')
<div class="card card-mb-16">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-slash"></i> LINEで送れなかった会員</h3>
    </div>
    <div class="card-body">
        <p class="form-text">お知らせをLINEで送るときに、ブロック中で送らなかった会員と、送信エラーになった会員の履歴です。現在ブロック中のLINEユーザー：<strong>{{ number_format($blockedUserCount) }}人</strong></p>
        <p class="form-text">ブロックは LINE の Webhook で受け取った時点から記録します。Webhook を設定する前のブロックは数えられません。</p>
    </div>
    <div class="status-filters">
        @foreach(['all' => 'すべて'] + \App\Models\LineDeliveryFailure::REASON_LABELS as $key => $label)
        <a href="{{ admin_route('admin.announcements.line-failures', $key === 'all' ? [] : ['reason' => $key]) }}" class="status-filter {{ $reason === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($failures->isEmpty())
        <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <p>送れなかった会員はいません</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>日時</th>
                        <th>会員</th>
                        <th>理由</th>
                        <th>お知らせ</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($failures as $failure)
                    @php
                        $name = trim((string) $failure->member?->user?->name);
                    @endphp
                    <tr>
                        <td>{{ $failure->created_at?->format('Y/m/d H:i') }}</td>
                        <td>
                            @if($failure->member !== null)
                            <a href="{{ admin_route('admin.members.detail', ['id' => $failure->member_id]) }}"><strong>{{ $name !== '' ? $name : 'ID:'.$failure->member_id }}</strong></a>
                            @else
                            退会済み
                            @endif
                        </td>
                        <td>{{ \App\Models\LineDeliveryFailure::REASON_LABELS[$failure->reason] ?? $failure->reason }}</td>
                        <td>
                            @if($failure->announcement !== null)
                            <a href="{{ admin_route('admin.announcements.reads', ['id' => $failure->announcement_id]) }}">{{ $failure->announcement->title }}</a>
                            @else
                            —
                            @endif
                        </td>
                        <td><small>{{ $failure->error_message ?? '—' }}</small></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $failures->links('vendor.pagination.admin') }}
        @endif
    </div>
</div>
@endsection
