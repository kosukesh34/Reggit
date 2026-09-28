<label class="ad-segment-operator" title="ここまでの条件との組み合わせ">
    <span class="sr-only">条件の組み合わせ</span>
    <select name="segment_operators[{{ $key }}]" class="segment-filter" aria-label="{{ $key }}の条件の組み合わせ">
        <option value="and" {{ ($operators[$key] ?? 'and') === 'and' ? 'selected' : '' }}>AND</option>
        <option value="or" {{ ($operators[$key] ?? 'and') === 'or' ? 'selected' : '' }}>OR</option>
    </select>
</label>
