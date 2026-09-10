@extends('layouts.lp')

@section('title', $view->title)

@section('content')
    <section class="lp-container lp-status">
        <h1>{{ $view->title }}</h1>
        <p>{{ $view->body }}</p>
        @if ($view->actionUrl !== null && $view->actionLabel !== null)
            <a class="lp-button lp-button--primary" href="{{ $view->actionUrl }}">{{ $view->actionLabel }}</a>
        @endif
    </section>
@endsection
