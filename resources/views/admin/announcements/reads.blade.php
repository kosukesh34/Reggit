@extends('admin.layout')

@section('title', '既読一覧')
@section('page-title', '既読一覧')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.announcements') }}">お知らせ</a>
    <span class="breadcrumb-sep">/</span>
    <span>既読一覧</span>
</nav>

<div class="page-header-row">
    <div class="page-header">
        <h1 class="page-header-title"><i class="fas fa-eye"></i> 既読一覧</h1>
        <p class="page-header-desc">{{ $announcement->title }}</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ admin_route('admin.announcements') }}" class="btn btn-secondary">一覧へ戻る</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">既読者</h3>
        <span class="card-header-note">{{ $readCount }}人</span>
    </div>
    <div class="card-body">
        @if($readCount === 0)
        <div class="empty-state">
            <i class="fas fa-eye-slash"></i>
            <p>まだ既読がありません</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>会員名</th>
                        <th>会員番号</th>
                        <th>既読日時</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reads as $read)
                    <tr>
                        <td>
                            @if($read['member_id'] !== null)
                                <a href="{{ admin_route('admin.members.detail', ['id' => $read['member_id']]) }}">{{ $read['member_name'] }}</a>
                            @else
                                {{ $read['member_name'] }}
                            @endif
                        </td>
                        <td>{{ $read['member_number'] }}</td>
                        <td>{{ $read['read_at'] }}</td>
                        <td class="table-actions-cell">
                            @if($read['member_id'] !== null)
                            <div class="table-actions">
                                <a href="{{ admin_route('admin.members.detail', ['id' => $read['member_id']]) }}" class="btn btn-action btn-edit"><i class="fas fa-eye"></i> 詳細</a>
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
@endsection
