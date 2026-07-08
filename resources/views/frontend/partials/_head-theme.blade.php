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
@isset($liffTabSettings['_global'])
@php
    $g = $liffTabSettings['_global'];
    $themeVars = \App\Support\LiffThemePresets::cssVariablesFor($g);
    $themeBodyClasses = \App\Support\LiffThemePresets::bodyClassesFor($g);
@endphp
<style id="liff-design-tokens">
:root {
@foreach($themeVars as $name => $value)
    {{ $name }}: {{ $value }};
@endforeach
@if(!empty($g['content_padding']))
    --content-padding: {{ (int) $g['content_padding'] }}px;
@endif
@if(!empty($g['section_gap']))
    --section-gap: {{ (int) $g['section_gap'] }}px;
@endif
@php
    $radiusMap = [
        'compact' => ['xs' => '6px', 'sm' => '10px', 'md' => '12px', 'lg' => '14px', 'xl' => '18px'],
        'default' => ['xs' => '8px', 'sm' => '12px', 'md' => '16px', 'lg' => '20px', 'xl' => '24px'],
        'soft'    => ['xs' => '10px', 'sm' => '14px', 'md' => '18px', 'lg' => '22px', 'xl' => '28px'],
        'round'   => ['xs' => '12px', 'sm' => '18px', 'md' => '24px', 'lg' => '28px', 'xl' => '32px'],
    ];
    $scale = $radiusMap[$g['radius_scale'] ?? 'soft'] ?? $radiusMap['soft'];
@endphp
    --radius-xs: {{ $scale['xs'] }};
    --radius-sm: {{ $scale['sm'] }};
    --radius: {{ $scale['md'] }};
    --radius-lg: {{ $scale['lg'] }};
    --radius-xl: {{ $scale['xl'] }};
}
</style>
@if($themeBodyClasses !== [])
<script>document.addEventListener('DOMContentLoaded',function(){document.body.classList.add.apply(document.body.classList,@json($themeBodyClasses));},{once:true});</script>
@endif
@php
    $bgImageUrl = $g['bg_image_url'] ?? null;
    if (($g['background_style'] ?? '') === 'image' && empty($bgImageUrl)) {
        $preset = \App\Support\LiffThemePresets::find($g['theme_preset'] ?? null);
        $bgImageUrl = $preset['bg_image_url'] ?? null;
    }
@endphp
@if(!empty($bgImageUrl))
<script>
document.addEventListener('DOMContentLoaded',function(){
    var img=new Image();
    img.onload=function(){document.body.classList.add('has-bg-image-loaded');};
    img.onerror=function(){document.body.classList.remove('app-bg-image');document.body.classList.add('app-bg-solid');};
    img.src=@json($bgImageUrl);
},{once:true});
</script>
@endif
@endisset
