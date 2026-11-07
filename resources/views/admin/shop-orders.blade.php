@extends('admin.layout')

@section('title', 'ショップ注文')
@section('page-title', 'ショップ注文')

@section('content')
@include('admin.partials._orders_list', [
    'pageTitle' => 'ショップ注文',
    'pageIcon' => 'fas fa-truck',
    'ordersIndexRoute' => 'admin.shop.orders',
    'ordersDetailRoute' => 'admin.shop.orders.detail',
    'ordersCompleteRoute' => 'admin.shop.orders.complete',
])
@endsection
