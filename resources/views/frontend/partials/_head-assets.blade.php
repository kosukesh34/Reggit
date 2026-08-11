{{-- 旧 frontend/style.css は DesignMock 全面刷新後は読み込まない（衝突回避） --}}
<style>
  #liff-loading { display: none !important; visibility: hidden !important; pointer-events: none !important; }
  body.reggit-liff-ui, .reggit-liff-ui-root { min-height: 100vh; }
</style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+JP:wght@400;500;700&display=swap">
@if(!(isset($isFixedUi) && $isFixedUi) && isset($liffCustomCss) && $liffCustomCss !== null && $liffCustomCss !== '')
    <style id="liff-custom-style">{!! $liffCustomCss !!}</style>
@endif
