@if($useInlineLocalAssets)
    @php
        $mainCss = @file_get_contents($cssDir . '/style.css') ?: '';
        $inlineCss = $mainCss;
        if (preg_match_all('/@import\s+url\([\'"]([^\'"]+)[\'"]\)\s*;/', $mainCss, $m)) {
            $inlineCss = '';
            foreach ($m[1] as $importPath) {
                // cache-bust 用 ?v= を除いて実ファイルを読む（ngrok インライン時）
                $relativePath = explode('?', $importPath, 2)[0];
                $full = $cssDir . '/' . $relativePath;
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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=M+PLUS+Rounded+1c:wght@700;800;900&display=swap">
@if(!(isset($isFixedUi) && $isFixedUi) && isset($liffCustomCss) && $liffCustomCss !== null && $liffCustomCss !== '')
    <style id="liff-custom-style">{!! $liffCustomCss !!}</style>
@endif
