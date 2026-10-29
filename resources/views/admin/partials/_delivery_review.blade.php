@php
    $isCouponDelivery = ($isCouponDelivery ?? false) === true;
@endphp
<div class="ad-review-modal" id="deliveryReviewModal" hidden>
    <button type="button" class="ad-review-backdrop" data-close-delivery-review aria-label="確認画面を閉じる"></button>
    <section class="ad-review-dialog" role="dialog" aria-modal="true" aria-labelledby="deliveryReviewTitle">
        <header class="ad-review-head">
            <div>
                <span>最終確認</span>
                <h2 id="deliveryReviewTitle">配信内容の最終確認</h2>
                <p>内容・対象者・通知方法を確認してから配信してください。</p>
            </div>
            <button type="button" class="ad-review-close" data-close-delivery-review aria-label="閉じる"><i class="fas fa-times" aria-hidden="true"></i></button>
        </header>

        <div class="ad-review-body" id="deliveryReviewBody">
            <div class="ad-review-grid">
                <section class="ad-review-card">
                    <h3><i class="fas fa-paper-plane" aria-hidden="true"></i> 配信設定</h3>
                    <dl>
                        <div><dt>届け先</dt><dd><button type="button" class="ad-review-audience-button" id="deliveryReviewAudienceButton" aria-expanded="false" aria-controls="deliveryReviewAudiencePanel"><span id="deliveryReviewAudienceLabel"></span><i class="fas fa-chevron-down" aria-hidden="true"></i></button></dd></div>
                        <div><dt>配信日時</dt><dd id="deliveryReviewTiming"></dd></div>
                        <div id="deliveryReviewResolveRow" hidden><dt>対象者の確定</dt><dd id="deliveryReviewResolve"></dd></div>
                        <div><dt>クーポン</dt><dd id="deliveryReviewCoupon"></dd></div>
                        @unless($isCouponDelivery)
                            <div><dt>固定表示</dt><dd id="deliveryReviewPinned"></dd></div>
                        @endunless
                    </dl>
                </section>

                <section class="ad-review-card ad-review-notifications">
                    <h3><i class="fas fa-bell" aria-hidden="true"></i> 通知予定</h3>
                    <p class="ad-review-notify-off" id="deliveryReviewNotifyOff" hidden>
                        @if($isCouponDelivery)
                            通知は送りません。クーポンの発行のみ行います。
                        @else
                            通知は送りません。アプリ内のお知らせには掲載されます。
                        @endif
                    </p>
                    <div class="ad-review-channel-list" id="deliveryReviewChannelList">
                        <div class="ad-review-channel ad-review-channel--line">
                            <span><i class="fab fa-line" aria-hidden="true"></i> LINE</span>
                            <strong id="deliveryReviewLineCount">0人</strong>
                        </div>
                        <div class="ad-review-channel ad-review-channel--push">
                            <span><i class="fas fa-mobile-screen-button" aria-hidden="true"></i> Push通知</span>
                            <strong id="deliveryReviewPushCount">0人</strong>
                        </div>
                    </div>
                    <p class="ad-review-quota" id="deliveryReviewQuota" hidden></p>
                    <p class="ad-review-unreachable" id="deliveryReviewOptedOut" hidden></p>
                    <p class="ad-review-unreachable" id="deliveryReviewUnreachable" hidden></p>
                </section>
            </div>

            <section class="ad-review-card ad-review-conditions" id="deliveryReviewConditionBlock" hidden>
                <h3>絞り込み条件</h3>
                <div class="ad-condition-chips" id="deliveryReviewConditions"></div>
            </section>

            <div class="ad-review-warning" id="deliveryReviewWarning">
                <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                <div><strong id="deliveryReviewWarningTitle"></strong><span id="deliveryReviewWarningText"></span></div>
            </div>
        </div>

        <footer class="ad-review-actions">
            <button type="button" class="btn btn-secondary" data-close-delivery-review><i class="fas fa-arrow-left" aria-hidden="true"></i> 内容を修正する</button>
            <button type="button" class="btn btn-primary" id="deliveryReviewConfirm"><i class="fas fa-paper-plane" aria-hidden="true"></i> <span id="deliveryReviewConfirmLabel">この内容で配信する</span></button>
        </footer>
    </section>
</div>

<div class="ad-member-detail-modal" id="audienceMemberDetailModal" hidden>
    <button type="button" class="ad-member-detail-backdrop" data-close-audience-member-detail aria-label="会員情報を閉じる"></button>
    <section class="ad-member-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="audienceMemberDetailTitle">
        <header>
            <div class="ad-member-detail-identity">
                <span id="audienceMemberDetailAvatar" aria-hidden="true">—</span>
                <div><small>配信予定ユーザー</small><h2 id="audienceMemberDetailTitle">会員情報</h2><p id="audienceMemberDetailNumber">—</p></div>
            </div>
            <button type="button" data-close-audience-member-detail aria-label="会員情報を閉じる"><i class="fas fa-times" aria-hidden="true"></i></button>
        </header>
        <div class="ad-member-detail-body">
            <div class="ad-member-detail-sendability">
                <span id="audienceMemberDetailSendableIcon"><i class="fas fa-bell" aria-hidden="true"></i></span>
                <div><strong id="audienceMemberDetailSendable">通知可否</strong><small id="audienceMemberDetailReason"></small></div>
            </div>
            <div class="ad-member-detail-tags" id="audienceMemberDetailTags"></div>
            <dl>
                <div><dt>通知設定</dt><dd id="audienceMemberDetailPreference">—</dd></div>
                <div><dt>登録チャネル</dt><dd id="audienceMemberDetailChannel">—</dd></div>
                <div><dt>ランク</dt><dd id="audienceMemberDetailRank">—</dd></div>
                <div><dt>保有ポイント</dt><dd id="audienceMemberDetailPoints">0pt</dd></div>
                <div><dt>生年月日</dt><dd id="audienceMemberDetailBirthday">—</dd></div>
                <div><dt>性別</dt><dd id="audienceMemberDetailSex">—</dd></div>
                <div><dt>最終来店</dt><dd id="audienceMemberDetailLastVisit">—</dd></div>
                <div><dt>電話番号</dt><dd id="audienceMemberDetailPhone">—</dd></div>
                <div class="ad-member-detail-wide"><dt>メールアドレス</dt><dd id="audienceMemberDetailEmail">—</dd></div>
            </dl>
        </div>
        <footer><button type="button" class="btn btn-secondary" data-close-audience-member-detail>閉じる</button></footer>
    </section>
</div>
