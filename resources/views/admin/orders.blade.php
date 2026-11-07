@extends('admin.layout')

@section('title', '注文管理')
@section('page-title', '注文管理')

@section('content')
@include('admin.partials._orders_list', [
    'pageTitle' => '注文管理',
    'pageIcon' => 'fas fa-shopping-cart',
    'ordersIndexRoute' => 'admin.orders',
    'ordersDetailRoute' => 'admin.orders.detail',
    'ordersCompleteRoute' => 'admin.orders.complete',
])
@endsection
