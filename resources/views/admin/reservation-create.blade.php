@extends('admin.layout')

@section('title', '予約作成')
@section('page-title', '予約作成')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.reservations') }}">予約</a>
    <span class="breadcrumb-sep">/</span>
    <span>新規作成</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-calendar-plus"></i> 予約を新規作成</h1>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-edit"></i> 予約情報</h3>
    </div>
    <div class="card-body">
        <form action="{{ admin_route('admin.reservations.store') }}" method="POST">
            @csrf
            @include('admin.partials._reservation_fields', ['reservation' => null])
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-large">
                    <i class="fas fa-check"></i> 予約を作成
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
