@php
    use App\Support\NativeApp;
    $userAgent = request()->header('User-Agent', '');
    $isNativeApp = $userAgent && preg_match(NativeApp::userAgentPattern(), $userAgent);
@endphp
@if($isNativeApp)
    @include('frontend.native')
@else
    @include('frontend.line')
@endif
