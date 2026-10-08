@php
    $oldDeliveryTiming = $oldDeliveryTiming ?? \App\Models\Announcement::DELIVERY_TIMING_IMMEDIATE;
    $oldResolveMode = old('audience_resolve_mode', \App\Enums\AnnouncementAudienceResolveMode::SNAPSHOT);
@endphp
<div class="andel-card" role="region" id="delivery-timing" aria-label="配信タイミング">
    <fieldset class="ad-delivery-fieldset">
        <legend>配信タイミング <span class="ad-required">必須</span></legend>
        <div class="ad-timing-types">
            <label class="ad-timing-choice {{ $oldDeliveryTiming === \App\Models\Announcement::DELIVERY_TIMING_IMMEDIATE ? 'is-selected' : '' }}">
                <input type="radio" name="delivery_timing" value="immediate" {{ $oldDeliveryTiming === \App\Models\Announcement::DELIVERY_TIMING_IMMEDIATE ? 'checked' : '' }}>
                <span class="ad-choice-icon"><i class="fas fa-bolt" aria-hidden="true"></i></span>
                <span><strong>即時配信</strong><small>確認後、すぐに配信します</small></span>
                <span class="ad-choice-check"><i class="fas fa-check" aria-hidden="true"></i></span>
            </label>
            <label class="ad-timing-choice {{ $oldDeliveryTiming === \App\Models\Announcement::DELIVERY_TIMING_SCHEDULED ? 'is-selected' : '' }}">
                <input type="radio" name="delivery_timing" value="scheduled" {{ $oldDeliveryTiming === \App\Models\Announcement::DELIVERY_TIMING_SCHEDULED ? 'checked' : '' }}>
                <span class="ad-choice-icon"><i class="far fa-calendar" aria-hidden="true"></i></span>
                <span><strong>予約配信</strong><small>指定した日時に配信します</small></span>
                <span class="ad-choice-check"><i class="fas fa-check" aria-hidden="true"></i></span>
            </label>
        </div>
    </fieldset>

    <div id="scheduledBlock" class="ad-context-panel" hidden>
        <span class="ad-context-icon"><i class="far fa-calendar" aria-hidden="true"></i></span>
        <div class="form-group mb-0">
            <label for="scheduled_at" class="form-label">配信日時 <span class="ad-required">必須</span></label>
            <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control" value="{{ old('scheduled_at') }}" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}">
        </div>
    </div>

    <div id="audienceResolveBlock" class="ad-context-panel ad-context-panel--stack" hidden>
        <div class="ad-context-title">
            <span class="ad-context-icon"><i class="fas fa-user-lock" aria-hidden="true"></i></span>
            <div><strong>対象者の確定タイミング</strong><small>予約配信では、今の一覧で固定するか、配信時刻に条件をやり直すかを選べます</small></div>
        </div>
        <div class="ad-timing-types">
            <label class="ad-timing-choice {{ $oldResolveMode === \App\Enums\AnnouncementAudienceResolveMode::SNAPSHOT ? 'is-selected' : '' }}">
                <input type="radio" name="audience_resolve_mode" value="{{ \App\Enums\AnnouncementAudienceResolveMode::SNAPSHOT }}" {{ $oldResolveMode === \App\Enums\AnnouncementAudienceResolveMode::SNAPSHOT ? 'checked' : '' }}>
                <span class="ad-choice-icon"><i class="fas fa-lock" aria-hidden="true"></i></span>
                <span><strong>今の対象者で固定する</strong><small>この画面で確認した会員一覧のまま配信します</small></span>
                <span class="ad-choice-check"><i class="fas fa-check" aria-hidden="true"></i></span>
            </label>
            <label class="ad-timing-choice {{ $oldResolveMode === \App\Enums\AnnouncementAudienceResolveMode::AT_SEND ? 'is-selected' : '' }}">
                <input type="radio" name="audience_resolve_mode" value="{{ \App\Enums\AnnouncementAudienceResolveMode::AT_SEND }}" {{ $oldResolveMode === \App\Enums\AnnouncementAudienceResolveMode::AT_SEND ? 'checked' : '' }}>
                <span class="ad-choice-icon"><i class="fas fa-rotate" aria-hidden="true"></i></span>
                <span><strong>配信時に条件を再評価する</strong><small>予約時刻に条件へ一致する会員へ配信します</small></span>
                <span class="ad-choice-check"><i class="fas fa-check" aria-hidden="true"></i></span>
            </label>
        </div>
    </div>
</div>
