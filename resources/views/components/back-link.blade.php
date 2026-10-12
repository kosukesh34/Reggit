@props(['href', 'label' => '戻る'])
<a href="{{ $href }}" {{ $attributes->class(['web-back']) }}>
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 5-7 7 7 7M5 12h14"/></svg>
    <span>{{ $label }}</span>
</a>
