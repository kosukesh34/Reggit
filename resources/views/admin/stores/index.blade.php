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
@if(auth('admin')->user()->hasPermission('settings.manage'))
    @foreach($stores->where('is_active', true) as $store)
        @php $editing = (int) old('form_store_id') === $store->id; @endphp
        <div class="card">
            <div class="card-header"><h3>{{ $store->name }} の店舗情報</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ admin_route('admin.stores.update', ['id' => $store->id]) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="form_store_id" value="{{ $store->id }}">
                    <div class="form-group">
                        <label class="form-label" for="store-address-{{ $store->id }}">住所</label>
                        <input type="text" id="store-address-{{ $store->id }}" name="address" class="form-control" maxlength="255" value="{{ $editing ? old('address') : $store->address }}">
                        @if($editing)@error('address')<p class="text-danger">{{ $message }}</p>@enderror @endif
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="store-phone-{{ $store->id }}">電話番号</label>
                        <input type="tel" id="store-phone-{{ $store->id }}" name="phone" class="form-control" maxlength="30" value="{{ $editing ? old('phone') : $store->phone }}">
                        @if($editing)@error('phone')<p class="text-danger">{{ $message }}</p>@enderror @endif
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="store-coordinates-{{ $store->id }}">座標（緯度, 経度）</label>
                        <input type="text" id="store-coordinates-{{ $store->id }}" name="coordinates" class="form-control" placeholder="33.5494, 130.4242" value="{{ $editing ? old('coordinates') : ($store->latitude !== null ? $store->latitude.', '.$store->longitude : '') }}">
                        <small class="form-text">Google マップで店舗を右クリックし、表示された数字をコピーして貼り付けてください。</small>
                        @if($editing)@error('coordinates')<p class="text-danger">{{ $message }}</p>@enderror @endif
                    </div>
                    <button class="btn btn-primary">店舗情報を保存</button>
                </form>
            </div>
        </div>
    @endforeach
@endif
@endsection
