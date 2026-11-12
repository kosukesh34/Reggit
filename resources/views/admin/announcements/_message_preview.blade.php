{{-- 入力中のお知らせが会員にどう届くかのイメージ。中身は announcementDelivery.ts の messagePreview が入力に合わせて更新する --}}
@php
    $showLinePreview = $view->linePlanEnabled;
@endphp
<aside class="ad-studio-aside" aria-label="会員への届き方のプレビュー">
    <div class="ad-preview-panel" id="messagePreview">
        <div class="ad-preview-head">
            <span><i class="fas fa-mobile-screen-button" aria-hidden="true"></i> 会員への届き方</span>
            @if($showLinePreview)
                <div class="ad-preview-tabs" role="group" aria-label="プレビューの切り替え">
                    <button type="button" data-preview-tab="line" aria-pressed="true"><i class="fab fa-line" aria-hidden="true"></i> LINE</button>
                    <button type="button" data-preview-tab="app" aria-pressed="false"><i class="fas fa-mobile-screen-button" aria-hidden="true"></i> アプリ</button>
                </div>
            @endif
        </div>

        <div class="ad-phone-preview">
            @if($showLinePreview)
                <div data-preview-pane="line">
                    <div class="ad-phone-bar"><i class="fas fa-chevron-left" aria-hidden="true"></i><strong>{{ $lineAccountName }}</strong><span></span></div>
                    <div class="ad-line-talk">
                        <p class="ad-preview-off" data-preview-notify-off hidden><i class="fas fa-bell-slash" aria-hidden="true"></i> 通知オフのため、LINEには送信されません</p>
                        <div class="ad-line-bubble" data-preview-notify-on>
                            <div class="ad-preview-media" data-preview-media hidden><img alt="" data-preview-image><span class="ad-preview-more" data-preview-more hidden></span></div>
                            <div class="ad-line-text">
                                <strong data-preview-title></strong>
                                <p data-preview-body hidden></p>
                            </div>
                            <span class="ad-line-button">お知らせを見る</span>
                        </div>
                        <div class="ad-preview-coupon" data-preview-coupon hidden><i class="fas fa-ticket-alt" aria-hidden="true"></i><span><strong data-preview-coupon-name></strong><small>クーポンも一緒に届きます</small></span></div>
                    </div>
                </div>
            @endif
            <div data-preview-pane="app" {{ $showLinePreview ? 'hidden' : '' }}>
                <div class="ad-phone-bar"><i class="fas fa-chevron-left" aria-hidden="true"></i><strong>お知らせ</strong><span></span></div>
                <div class="ad-push-banner" data-preview-notify-on>
                    <span class="ad-push-icon" aria-hidden="true"><i class="fas fa-bell"></i></span>
                    <span><small>Push通知</small><strong data-preview-title></strong></span>
                </div>
                <article class="ad-announcement-preview">
                    <div class="ad-preview-media" data-preview-media hidden><img alt="" data-preview-image><span class="ad-preview-more" data-preview-more hidden></span></div>
                    <div class="ad-preview-content">
                        <div class="ad-preview-badges" data-preview-pinned hidden><span><i class="fas fa-thumbtack" aria-hidden="true"></i> 固定</span></div>
                        <h3 data-preview-title></h3>
                        <p data-preview-body hidden></p>
                        <div class="ad-preview-coupon" data-preview-coupon hidden><i class="fas fa-ticket-alt" aria-hidden="true"></i><span><strong data-preview-coupon-name></strong><small>クーポン</small></span></div>
                    </div>
                </article>
            </div>
        </div>
        <p class="ad-preview-note">表示のイメージです。実際の見た目は端末により多少異なります。</p>
    </div>
</aside>
