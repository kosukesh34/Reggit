@php
    $userAgent = request()->header('User-Agent', '');
    $isNativeApp = $userAgent && preg_match('/MobileOrderApp\/\d+/i', $userAgent);
@endphp
@if($isNativeApp)
    @include('frontend.native')
@else
    @include('frontend.line')
@endif
