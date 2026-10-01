@extends('admin.layout')
@section('title', 'アプリデザイン')
@section('page-title', 'アプリデザイン')
@section('content')
@include('admin.settings._app-design-navigation', ['activeDesign' => 'card'])
<form action="{{ admin_route('admin.settings.member-card.update') }}" method="POST" class="member-card-settings">
    @csrf
    @include('admin.settings._member-card-fields')
    <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 会員証デザインを保存</button></div>
</form>
@endsection
