@isset($lineTheme)
<style>
:root {
    --primary-color: {{ $lineTheme['primary'] }};
    --primary-rgb: {{ $lineTheme['primary_rgb'] ?? '0, 0, 0' }};
    --primary-dark: {{ $lineTheme['primary_dark'] }};
    --success-color: {{ $lineTheme['success'] }};
    --success-dark: {{ $lineTheme['success'] }};
    --danger: {{ $lineTheme['danger'] }};
}
</style>
@endisset
