{{-- 旧 frontend/style.css は DesignMock 全面刷新後は読み込まない（衝突回避） --}}
<style>
  #liff-loading { display: none !important; }
  body.reggit-liff-ui, .reggit-liff-ui-root { min-height: 100vh; }
</style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Noto+Sans+JP:wght@400;500;700&display=swap">
@if(!(isset($isFixedUi) && $isFixedUi) && isset($liffCustomCss) && $liffCustomCss !== null && $liffCustomCss !== '')
    <style id="liff-custom-style">{!! $liffCustomCss !!}</style>
@endif
