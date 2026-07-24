@extends('admin.layout')
@section('title', 'ガチャ・スクラッチ設定')
@section('page-title', 'ガチャ・スクラッチ設定')

@section('content')
<nav class="breadcrumb">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.coupons') }}">クーポン</a>
    <span class="breadcrumb-sep">/</span>
    <span>ガチャ・スクラッチ設定</span>
</nav>
<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-dice"></i> ガチャ・スクラッチ設定</h1>
    <p class="page-header-desc">取引後に付与する権利、景品クーポン、当選確率を設定します。権利は画面を開いていなくてもDBに保存されます。</p>
</div>
@include('admin.settings._context', ['pageKey' => 'lottery'])
@if(session('success'))
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $gachaPrizes = old('prizes')
        ? collect(old('prizes'))->where('reward_type', 'gacha')->values()
        : $prizes->where('reward_type', 'gacha')->values();
    $scratchPrizes = old('prizes')
        ? collect(old('prizes'))->where('reward_type', 'scratch')->values()
        : $prizes->where('reward_type', 'scratch')->values();
@endphp

<form action="{{ admin_route('admin.settings.lottery.update') }}" method="POST" id="lotterySettingsForm">
    @csrf
    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-toggle-on"></i> 機能・付与条件</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="checkbox-label checkbox-block">
                    <input type="hidden" name="gacha_enabled" value="0">
                    <input type="checkbox" name="gacha_enabled" value="1" {{ old('gacha_enabled', $settings->gacha_enabled) ? 'checked' : '' }}>
                    <span>ガチャを有効にする</span>
                </label>
            </div>
            <div class="form-group">
                <label class="checkbox-label checkbox-block">
                    <input type="hidden" name="scratch_enabled" value="0">
                    <input type="checkbox" name="scratch_enabled" value="1" {{ old('scratch_enabled', $settings->scratch_enabled) ? 'checked' : '' }}>
                    <span>スクラッチを有効にする</span>
                </label>
            </div>
            <div class="form-group">
                <label for="grant_trigger" class="form-label">権利の付与タイミング</label>
                <select name="grant_trigger" id="grant_trigger" class="form-control" required>
                    @foreach($grantTriggerOptions as $value => $label)
                        <option value="{{ $value }}" {{ old('grant_trigger', $settings->grant_trigger) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" id="min_amount_group" style="{{ old('grant_trigger', $settings->grant_trigger) === 'min_amount' ? '' : 'display:none;' }}">
                <label for="min_amount" class="form-label">最低金額（円）</label>
                <input type="number" step="1" min="0" name="min_amount" id="min_amount" class="form-control input-max-200"
                       value="{{ old('min_amount', $settings->min_amount) }}" placeholder="例: 1000">
                <p class="form-text text-muted">この金額以上の取引で権利を付与します。</p>
            </div>
            <div class="form-group">
                <label for="entitlement_expires_hours" class="form-label">権利の有効期限（時間）</label>
                <input type="number" min="1" max="8760" name="entitlement_expires_hours" id="entitlement_expires_hours" class="form-control input-max-200"
                       value="{{ old('entitlement_expires_hours', $settings->entitlement_expires_hours) }}" placeholder="空欄で無期限">
                <p class="form-text text-muted">未設定の場合は無期限です。例: 24 = 付与から24時間以内にプレイ。</p>
            </div>
        </div>
    </div>

    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-gift"></i> ガチャ景品（確率合計 100）</h3>
        </div>
        <div class="card-body">
            <div id="gachaPrizeRows" data-reward-type="gacha"></div>
            <button type="button" class="btn btn-secondary btn-sm" data-add-prize="gacha"><i class="fas fa-plus"></i> 景品を追加</button>
            <p class="form-text text-muted mt-2">合計: <strong id="gachaProbSum">0</strong> / 100</p>
        </div>
    </div>

    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-hand-pointer"></i> スクラッチ景品（確率合計 100）</h3>
        </div>
        <div class="card-body">
            <div id="scratchPrizeRows" data-reward-type="scratch"></div>
            <button type="button" class="btn btn-secondary btn-sm" data-add-prize="scratch"><i class="fas fa-plus"></i> 景品を追加</button>
            <p class="form-text text-muted mt-2">合計: <strong id="scratchProbSum">0</strong> / 100</p>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
        <a href="{{ admin_route('admin.coupons') }}" class="btn btn-secondary">クーポン一覧へ</a>
    </div>
</form>

<template id="lotteryPrizeRowTemplate">
    <div class="lottery-prize-row flex-row-gap-8" style="display:flex; gap:8px; align-items:flex-end; margin-bottom:12px; flex-wrap:wrap;">
        <input type="hidden" data-field="reward_type" value="">
        <div class="form-group" style="margin:0; min-width:220px; flex:1;">
            <label class="form-label">クーポン</label>
            <select data-field="coupon_id" class="form-control" required>
                <option value="">選択してください</option>
                @foreach($coupons as $coupon)
                    <option value="{{ $coupon->id }}">{{ $coupon->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin:0; width:110px;">
            <label class="form-label">確率</label>
            <input type="number" data-field="probability" class="form-control" min="1" max="100" value="0" required>
        </div>
        <button type="button" class="btn btn-danger btn-sm" data-remove-prize aria-label="削除"><i class="fas fa-trash"></i></button>
    </div>
</template>

<script>
(function () {
    const couponsEmpty = {{ $coupons->isEmpty() ? 'true' : 'false' }};
    const initial = {
        gacha: @json($gachaPrizes->map(fn ($p) => [
            'coupon_id' => (int) (is_array($p) ? ($p['coupon_id'] ?? 0) : $p->coupon_id),
            'probability' => (int) (is_array($p) ? ($p['probability'] ?? 0) : $p->probability),
        ])->values()),
        scratch: @json($scratchPrizes->map(fn ($p) => [
            'coupon_id' => (int) (is_array($p) ? ($p['coupon_id'] ?? 0) : $p->coupon_id),
            'probability' => (int) (is_array($p) ? ($p['probability'] ?? 0) : $p->probability),
        ])->values()),
    };
    let prizeIndex = 0;

    function containerFor(type) {
        return document.getElementById(type === 'gacha' ? 'gachaPrizeRows' : 'scratchPrizeRows');
    }

    function sumEl(type) {
        return document.getElementById(type === 'gacha' ? 'gachaProbSum' : 'scratchProbSum');
    }

    function refreshSums() {
        ['gacha', 'scratch'].forEach((type) => {
            let sum = 0;
            containerFor(type).querySelectorAll('[data-field="probability"]').forEach((input) => {
                sum += parseInt(input.value, 10) || 0;
            });
            const el = sumEl(type);
            el.textContent = String(sum);
            el.style.color = sum === 100 ? '#0d9488' : '#dc3545';
        });
    }

    function reindexNames() {
        prizeIndex = 0;
        ['gacha', 'scratch'].forEach((type) => {
            containerFor(type).querySelectorAll('.lottery-prize-row').forEach((row) => {
                const i = prizeIndex++;
                row.querySelectorAll('[data-field]').forEach((el) => {
                    const field = el.getAttribute('data-field');
                    el.name = `prizes[${i}][${field}]`;
                });
            });
        });
        refreshSums();
    }

    function addRow(type, data) {
        const tpl = document.getElementById('lotteryPrizeRowTemplate');
        const node = tpl.content.cloneNode(true);
        const row = node.querySelector('.lottery-prize-row');
        row.querySelector('[data-field="reward_type"]').value = type;
        const couponSelect = row.querySelector('[data-field="coupon_id"]');
        const probInput = row.querySelector('[data-field="probability"]');
        if (data && data.coupon_id) {
            couponSelect.value = String(data.coupon_id);
        }
        if (data && data.probability) {
            probInput.value = String(data.probability);
        }
        row.querySelector('[data-remove-prize]').addEventListener('click', () => {
            row.remove();
            reindexNames();
        });
        probInput.addEventListener('input', refreshSums);
        containerFor(type).appendChild(row);
        reindexNames();
    }

    document.querySelectorAll('[data-add-prize]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (couponsEmpty) {
                alert('先に有効なクーポンを作成してください。');
                return;
            }
            addRow(btn.getAttribute('data-add-prize'), { probability: 0 });
        });
    });

    document.getElementById('grant_trigger').addEventListener('change', function () {
        document.getElementById('min_amount_group').style.display = this.value === 'min_amount' ? '' : 'none';
    });

    initial.gacha.forEach((p) => addRow('gacha', p));
    initial.scratch.forEach((p) => addRow('scratch', p));
})();
</script>
@endsection
