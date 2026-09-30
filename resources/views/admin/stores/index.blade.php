@extends('admin.layout')
@section('title', '利用店舗')
@section('page-title', '利用店舗')
@section('content')
<div class="card">
    <div class="card-header"><h3>Reggitで利用する店舗</h3></div>
    <div class="card-body">
        @if($canManageStores)
            <form method="POST" action="{{ admin_route('admin.stores.active') }}">
                @csrf @method('PUT')
                @foreach($availableSmaregiStores as $store)
                    <label style="display:block;margin:12px 0">
                        <input type="checkbox" name="store_ids[]" value="{{ $store->id }}" @checked(in_array($store->id, old('store_ids', $stores->where('is_active', true)->pluck('id')->all())))>
                        <strong>{{ $store->name }}</strong> <small>（スマレジ店舗ID: {{ $store->smaregi_store_id }}）</small>
                    </label>
                @endforeach
                @error('store_ids')<p class="text-danger">{{ $message }}</p>@enderror
                <button class="btn btn-primary">利用店舗を保存</button>
            </form>
        @else
            @foreach($stores->where('is_active', true) as $store)
                <p>{{ $store->name }}</p>
            @endforeach
        @endif
    </div>
</div>
@endsection
