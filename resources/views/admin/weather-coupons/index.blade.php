@extends('admin.layout')
@section('title', '雨の日クーポン')
@section('page-title', '雨の日クーポン')
@section('content')
@php
    $canManage = auth('admin')->user()?->hasPermission('coupons.manage');
    $field = fn (string $name) => old($name, $editing?->{$name});
    // 未チェックのチェックボックスは送信されないため、入力し直しのときは送信値だけを見る
    $checked = fn (string $name) => session()->hasOldInput() ? old($name) !== null : ($editing?->{$name} ?? true);
@endphp
<div class="card">
    <div class="card-header"><h2 class="card-title"><i class="fas fa-list"></i> 配布ルール</h2></div>
    <div class="card-body">
        <p class="form-text">店舗の座標の天気予報で、営業時間内に雨が続くと判定した日に、その営業日だけ使えるクーポンを配ります。15分ごとに判定し、営業中は1時間ごとに判定し直します。</p>
        @if($rules->isEmpty())
            <p>まだ配布ルールはありません。</p>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>店舗</th><th>クーポン</th><th>雨の基準</th><th>判定する時間</th><th>週の上限</th><th>配る相手</th><th>配り方</th><th>状態</th>@if($canManage)<th>操作</th>@endif</tr></thead>
                    <tbody>
                    @foreach($rules as $rule)
                        <tr>
                            <td>
                                {{ $rule->store?->name }}
                                @if($rule->store?->latitude === null)<br><small class="text-danger">座標が未設定のため判定しません</small>@endif
                            </td>
                            <td>{{ $rule->coupon?->name }}</td>
                            <td>降水確率 {{ $rule->min_precipitation_probability }}% 以上が {{ $rule->min_rain_hours }}時間続く</td>
                            <td>開店 {{ $rule->check_hours_before_open }}時間前 〜 閉店 {{ $rule->cutoff_hours_before_close }}時間前</td>
                            <td>{{ $rule->weekly_limit }}回</td>
                            <td>{{ $rule->target === \App\Models\WeatherCouponRule::TARGET_ALL_MEMBERS ? '全会員' : '店舗の会員' }}</td>
                            <td>{{ $rule->requires_approval ? '承認してから配る' : '自動で配る' }}</td>
                            <td>{{ $rule->is_active ? '有効' : '停止中' }}</td>
                            @if($canManage)
                                <td>
                                    <a href="{{ admin_route('admin.weather-coupons.index', ['edit' => $rule->id]) }}#rule-form" class="btn btn-secondary btn-sm">編集</a>
                                    <form method="POST" action="{{ admin_route('admin.weather-coupons.rules.destroy', ['id' => $rule->id]) }}" style="display:inline" onsubmit="return confirm('この配布ルールを削除しますか？')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">削除</button></form>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@if($canManage)
<div class="card" id="rule-form">
    <div class="card-header"><h2 class="card-title">{{ $editing ? '配布ルールを編集' : '配布ルールを登録' }}</h2></div>
    <div class="card-body">
        @if($stores->isEmpty())
            <p>利用中の店舗がありません。</p>
        @else
        <form method="POST" action="{{ $editing ? admin_route('admin.weather-coupons.rules.update', ['id' => $editing->id]) : admin_route('admin.weather-coupons.rules.store') }}">
            @csrf
            @if($editing) @method('PUT') @endif
            <div class="form-group">
                <label class="form-label" for="rule-store">店舗</label>
                <select id="rule-store" name="store_id" class="form-control" required>
                    <option value="">選択してください</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" @selected((int) $field('store_id') === $store->id)>{{ $store->name }}{{ $store->latitude === null ? '（座標が未設定）' : '' }}</option>
                    @endforeach
                </select>
                <small class="form-text">座標は「利用店舗」で設定します。</small>
                @error('store_id')<p class="text-danger">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="rule-coupon">配るクーポン</label>
                <select id="rule-coupon" name="coupon_id" class="form-control" required>
                    <option value="">選択してください</option>
                    @foreach($coupons as $coupon)
                        <option value="{{ $coupon->id }}" @selected((int) $field('coupon_id') === $coupon->id)>{{ $coupon->name }}</option>
                    @endforeach
                </select>
                @error('coupon_id')<p class="text-danger">{{ $message }}</p>@enderror
            </div>
            @foreach([
                'min_precipitation_probability' => ['降水確率（%）', 10, 100, '営業時間内の1時間ごとの降水確率がこの値以上の時間を「雨」とします。'],
                'min_rain_hours' => ['雨が続く時間（時間）', 1, 24, 'にわか雨で配らないよう、雨がこの時間以上続くときだけ配ります。'],
                'check_hours_before_open' => ['判定を始める時刻（開店の何時間前）', 0, 3, ''],
                'cutoff_hours_before_close' => ['配る締め切り（閉店の何時間前）', 0, 23, '届いても使う時間がないため、この時刻を過ぎたら配りません。'],
                'weekly_limit' => ['週の上限（回）', 1, 7, '月曜〜日曜で数えます。'],
            ] as $name => [$label, $min, $max, $hint])
                <div class="form-group">
                    <label class="form-label" for="rule-{{ $name }}">{{ $label }}</label>
                    <input type="number" id="rule-{{ $name }}" name="{{ $name }}" class="form-control" min="{{ $min }}" max="{{ $max }}" value="{{ $field($name) }}" required>
                    @if($hint !== '')<small class="form-text">{{ $hint }}</small>@endif
                    @error($name)<p class="text-danger">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <div class="form-group">
                <span class="form-label">配る相手</span>
                <label><input type="radio" name="target" value="{{ \App\Models\WeatherCouponRule::TARGET_STORE_MEMBERS }}" @checked($field('target') !== \App\Models\WeatherCouponRule::TARGET_ALL_MEMBERS)> その店舗の会員</label>
                <label><input type="radio" name="target" value="{{ \App\Models\WeatherCouponRule::TARGET_ALL_MEMBERS }}" @checked($field('target') === \App\Models\WeatherCouponRule::TARGET_ALL_MEMBERS)> 全会員</label>
                @error('target')<p class="text-danger">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="requires_approval" value="1" @checked($checked('requires_approval'))> 配る前にメールで承認を求める</label>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="is_active" value="1" @checked($checked('is_active'))> このルールを有効にする</label>
            </div>
            <button class="btn btn-primary">{{ $editing ? '更新する' : '登録する' }}</button>
            @if($editing)<a href="{{ admin_route('admin.weather-coupons.index') }}" class="btn btn-secondary">キャンセル</a>@endif
        </form>
        @endif
    </div>
</div>
@endif
<div class="card">
    <div class="card-header"><h2 class="card-title"><i class="fas fa-cloud-rain"></i> 最近の判定結果</h2></div>
    <div class="card-body">
        <p class="form-text">承認待ちの配布は、期限までに承認しないと配りません。「今日は配らない」を選ぶと、その日は判定も承認のメールも止まります。</p>
        @if($runs->isEmpty())
            <p>まだ判定結果はありません。</p>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>営業日</th><th>店舗</th><th>クーポン</th><th>状態</th><th>最大降水確率</th><th>配布数</th><th>操作</th></tr></thead>
                    <tbody>
                    @foreach($runs as $run)
                        @php
                            $pending = in_array($run->status, [\App\Models\WeatherCouponRun::STATUS_AWAITING_APPROVAL, \App\Models\WeatherCouponRun::STATUS_EXPIRED], true);
                            $awaiting = $run->status === \App\Models\WeatherCouponRun::STATUS_AWAITING_APPROVAL && $run->approval_expires_at?->isFuture();
                            $status = $pending && ! $awaiting ? \App\Models\WeatherCouponRun::STATUS_EXPIRED : $run->status;
                        @endphp
                        <tr>
                            <td>{{ $run->business_date->format('Y/m/d') }}</td>
                            <td>{{ $run->rule?->store?->name }}</td>
                            <td>{{ $run->rule?->coupon?->name }}</td>
                            <td>
                                {{ \App\Models\WeatherCouponRun::STATUS_LABELS[$status] ?? $status }}
                                @if($awaiting)<br><small>{{ $run->approval_expires_at->format('H:i') }} まで</small>@endif
                            </td>
                            <td>{{ $run->max_precipitation_probability !== null ? $run->max_precipitation_probability.'%' : '—' }}</td>
                            <td>{{ number_format($run->issued_count) }}人</td>
                            <td>
                                @if($canManage && $awaiting)
                                    <form method="POST" action="{{ admin_route('admin.weather-coupons.approve', ['id' => $run->id]) }}" style="display:inline">@csrf<button class="btn btn-primary btn-sm">承認して配る</button></form>
                                @endif
                                @if($canManage && $pending)
                                    <form method="POST" action="{{ admin_route('admin.weather-coupons.decline', ['id' => $run->id]) }}" style="display:inline">@csrf<button class="btn btn-secondary btn-sm">今日は配らない</button></form>
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
