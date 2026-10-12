@extends('layouts.lp')

@section('title', e($article['title']))

@section('content')
<article class="lp-container public-help help-manual">
    <p class="help-back"><a href="{{ route('help.index') }}">ヘルプ一覧へ戻る</a></p>
    <h1>{{ $article['title'] }}</h1>
    <p class="help-lead">{{ $article['summary'] }}</p>
    <p class="public-help-note">アプリ画面へのリンクはデモを開きます。画像もデモ例です。実際の店舗での操作は、ご利用中の管理画面・アプリで行ってください。</p>
    <div class="help-markdown">{!! $html !!}</div>
    <p class="help-back"><a href="{{ route('help.index') }}">ほかの機能のヘルプを探す</a></p>
</article>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/components/12-help-manual.css') }}">
<link rel="stylesheet" href="{{ asset('css/help-public.css') }}">
@endpush
