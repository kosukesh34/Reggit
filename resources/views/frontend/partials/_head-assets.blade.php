@if($useInlineLocalAssets)
    @php
        $mainCss = @file_get_contents($cssDir . '/style.css') ?: '';
        $inlineCss = $mainCss;
        if (preg_match_all('/@import\s+url\([\'"]([^\'"]+)[\'"]\)\s*;/', $mainCss, $m)) {
            $inlineCss = '';
            foreach ($m[1] as $importPath) {
                $full = $cssDir . '/' . $importPath;
                if (is_file($full)) {
                    $inlineCss .= file_get_contents($full) . "\n";
                }
            }
        }
    @endphp
    <style id="inline-frontend-style">{!! $inlineCss !!}</style>
@else
    <link rel="stylesheet" href="/css/frontend/style.css?v={{ urlencode($assetVersion) }}">
@endif
@if(isset($liffCustomCss) && $liffCustomCss !== null && $liffCustomCss !== '')
    <style id="liff-custom-style">{!! $liffCustomCss !!}</style>
@endif
