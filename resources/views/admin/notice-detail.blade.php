@extends('admin.layout')

@section('title', $notice->title)
@section('page-title', 'お知らせ')

@section('content')
<div class="dashboard-notice-page">

    <article class="dashboard-notice-article">
        <p class="dashboard-notice-date">作成日時：<time datetime="{{ $notice->created_at->toIso8601String() }}">{{ $notice->created_at->format('Y年m月d日 H:i') }}</time></p>
        <h1>{{ $notice->title }}</h1>
        <div class="notice-markdown">{!! app(\App\Support\NoticeMarkdown::class)->toHtml($notice->body) !!}</div>
    </article>
</div>
@endsection
