@extends('admin.layout')
@section('title', 'チャット')
@section('page-title', 'チャット')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/chat.css') }}?v={{ (int) @filemtime(public_path('css/admin/chat.css')) }}">
@endpush

@section('content')
<div class="page-header">
    <h1 class="page-header-title">チャット</h1>
</div>

@if(! $configured)
<div class="alert alert-warning">チャットの接続設定（Firebase）がありません。システム管理者に設定を依頼してください。</div>
@else
<div class="card admin-chat-card">
    <div id="admin-chat-root" aria-live="polite">
        {{-- 画面の JavaScript が動くまでの表示。resources/js/admin/chat.tsx の AdminChatSkeleton と同じ形 --}}
        <div class="admin-chat" aria-busy="true" aria-label="チャットを読み込み中">
            <div class="admin-chat-threads" aria-hidden="true">
                @for ($row = 0; $row < 5; $row++)
                <div class="admin-chat-thread"><span class="admin-chat-skeleton-line is-name"></span><span class="admin-chat-skeleton-line is-time"></span></div>
                @endfor
            </div>
            <div class="admin-chat-conversation chat-view">
                <div class="chat-messages">
                    <div class="chat-skeleton">
                        <span class="chat-skeleton-bubble is-other w-60"></span>
                        <span class="chat-skeleton-bubble is-other w-40"></span>
                        <span class="chat-skeleton-bubble is-mine w-50"></span>
                        <span class="chat-skeleton-bubble is-other w-70"></span>
                        <span class="chat-skeleton-bubble is-mine w-35"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="application/json" id="adminChatConfig">@json($chatConfig)</script>
@endif
@endsection
