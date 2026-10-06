<div class="ad-value-mode" data-value-mode="{{ $target }}" hidden>
    <span>{{ $caption }}</span>
    @foreach(\App\Enums\SegmentCombineMode::getValues() as $mode => $modeLabel)
        <label><input type="radio" name="{{ $name }}" value="{{ $mode }}" class="segment-filter" @checked(\App\Enums\SegmentCombineMode::fromInput($current) === $mode)> {{ $modeLabel }}</label>
    @endforeach
</div>
