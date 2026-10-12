@extends('admin.layout')

@section('title', $article['title'])
@section('page-title', 'ヘルプ')

@section('content')
<article class="help-manual">

    <h1>{{ $article['title'] }}</h1>
    <p class="help-lead">{{ $article['summary'] }}</p>
    @if($html !== '')
    <div class="help-markdown">{!! $html !!}</div>
    @else
    <ol class="help-steps">
        @foreach($article['steps'] as $step)
        <li>
            <strong>{{ $step['title'] }}</strong>
            <p>{{ $step['body'] }}</p>
            <figure class="help-shot">
                <div class="help-shot__frame">
                    <img src="{{ asset($step['image']) }}?v={{ (int) (@filemtime(public_path($step['image'])) ?: time()) }}" alt="{{ $step['alt'] }}">
                </div>
                <figcaption>{{ $step['caption'] }}</figcaption>
            </figure>
        </li>
        @endforeach
    </ol>
    @endif
    @if(($article['note'] ?? '') !== '')
    <div class="help-callout help-callout--caution">
        <strong>注意</strong>
        {{ $article['note'] }}
    </div>
    @endif
</article>
@endsection
