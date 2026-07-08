@extends('admin.layout')

@section('title', 'LIFFデザイン')
@section('page-title', 'LIFFデザイン')
@section('page-kind', 'liff-builder')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.settings.basic') }}">設定</a>
    <span class="breadcrumb-sep">/</span>
    <span>LIFFデザイン</span>
</nav>

<div class="card card-mb-24">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-layer-group"></i> テンプレート</h3>
    </div>
    <div class="card-body">
        <div class="builder-row-gap-12">
            @foreach($templates as $template)
                <button type="button" class="btn btn-secondary btn-sm" data-template-id="{{ $template['id'] }}">{{ $template['name'] }}</button>
            @endforeach
        </div>
        <p class="form-text text-muted builder-mt-8">テンプレートを選ぶと下の編集内容が置き換わります。</p>
    </div>
</div>

<form action="{{ admin_route('admin.settings.liff-builder.update') }}" method="POST" id="liffBuilderForm">
    @csrf

    <div class="card card-mb-24">
        <div class="card-header tabs-header-row">
            <h3 class="card-title"><i class="fas fa-pen"></i> ブロック編集</h3>
            <button type="button" class="btn btn-secondary btn-sm" id="addBlockBtn"><i class="fas fa-plus"></i> ブロック追加</button>
        </div>
        <div class="card-body" id="builderBlocks"></div>
    </div>

    <div class="card card-mb-24">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-eye"></i> プレビュー</h3>
        </div>
        <div class="card-body">
            <div id="builderPreview" class="builder-preview"></div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
    </div>
</form>

<template id="blockTemplate">
    <div class="builder-block-editor card builder-mb-16">
        <div class="card-header tabs-header-row">
            <strong class="builder-block-title">ブロック</strong>
            <button type="button" class="btn btn-danger btn-sm btn-remove-block"><i class="fas fa-trash"></i> 削除</button>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">タイプ</label>
                <select class="form-control block-type">
                    <option value="hero">ヒーロー</option>
                    <option value="text">テキスト</option>
                    <option value="image">画像</option>
                    <option value="button">ボタン</option>
                    <option value="list">リスト</option>
                    <option value="form">フォーム</option>
                    <option value="gallery">ギャラリー</option>
                    <option value="spacer">スペーサー</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">タイトル</label>
                <input type="text" class="form-control block-title" maxlength="120">
            </div>
            <div class="form-group">
                <label class="form-label">本文</label>
                <textarea class="form-control block-body" rows="3" maxlength="1000"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">画像URL</label>
                <input type="url" class="form-control block-image" maxlength="500" placeholder="https://...">
            </div>
            <div class="form-group">
                <label class="form-label">ボタン</label>
                <div class="builder-row-gap-8">
                    <input type="text" class="form-control block-button-label" placeholder="ラベル" maxlength="50">
                    <input type="text" class="form-control block-button-url" placeholder="URL or #tab" maxlength="500">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">リスト（カンマ区切り）</label>
                <input type="text" class="form-control block-list" maxlength="1000">
            </div>
            <div class="form-group form-row">
                <label class="form-label">整列</label>
                <select class="form-control block-align">
                    <option value="left">左</option>
                    <option value="center">中央</option>
                    <option value="right">右</option>
                </select>
                <label class="form-label">背景色</label>
                <input type="text" class="form-control block-bg" placeholder="#ffffff">
                <label class="form-label">文字色</label>
                <input type="text" class="form-control block-text-color" placeholder="#111111">
            </div>
            <div class="form-group block-form-fields">
                <label class="form-label">フォーム項目</label>
                <div class="form-fields-list"></div>
                <button type="button" class="btn btn-secondary btn-sm add-form-field"><i class="fas fa-plus"></i> 項目追加</button>
                <div class="builder-mt-8">
                    <input type="text" class="form-control block-form-action" placeholder="送信先URL">
                    <input type="text" class="form-control block-form-button builder-mt-8" placeholder="送信ボタンラベル">
                </div>
            </div>
            <div class="form-group block-spacer-field">
                <label class="form-label">高さ</label>
                <input type="number" class="form-control block-spacer-height" value="24" min="4" max="200">
            </div>
        </div>
    </div>
</template>

<template id="formFieldTemplate">
    <div class="form-field-row builder-row-gap-8 builder-mb-8">
        <input type="text" class="form-control field-label" placeholder="ラベル">
        <select class="form-control field-type">
            <option value="text">テキスト</option>
            <option value="email">メール</option>
            <option value="tel">電話</option>
            <option value="textarea">テキストエリア</option>
        </select>
        <input type="text" class="form-control field-placeholder" placeholder="プレースホルダー">
        <label class="builder-inline-flex">
            <input type="checkbox" class="field-required"> 必須
        </label>
        <button type="button" class="btn btn-danger btn-sm remove-form-field"><i class="fas fa-times"></i></button>
    </div>
</template>

<script id="liffBuilderPageConfig" type="application/json">
{!! json_encode(['templates' => $templates ?? [], 'blocks' => $blocks ?? []], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
<script>
    (function () {
        var el = document.getElementById('liffBuilderPageConfig');
        try {
            window.LiffBuilderPageConfig = el ? JSON.parse(el.textContent || '{}') : {};
        } catch (e) {
            window.LiffBuilderPageConfig = {};
        }
    })();
</script>
<style>
    .builder-preview .builder-container { padding: 0; }
    .builder-preview .builder-block { box-shadow: none; }
    .builder-preview { border: 1px dashed #ddd; padding: 16px; border-radius: 12px; background: #fafafa; }
    .form-row { display:flex; gap:8px; flex-wrap:wrap; }
</style>
@endsection
