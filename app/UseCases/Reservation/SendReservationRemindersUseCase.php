<?php

namespace App\UseCases\Reservation;

use App\Domain\Reservation\Policies\ReservationReminderPolicy;
use App\Domain\Reservation\Repositories\ReservationReminderRepository;
use App\Domain\Reservation\ValueObjects\ReservationReminderCandidate;
use App\Domain\Reservation\ValueObjects\ReservationReminderConfig;
use App\Domain\Settings\Repositories\FeatureSettings;
use App\Domain\Settings\Repositories\ReservationSettings;
use App\Services\PersonalAnnouncementSender;
use App\Util\LogHelper;
use Carbon\CarbonImmutable;

/** 予約設定の時間前になった予約へ、会員本人宛てのリマインドを送る（15分ごと・1予約につき最大2回）。 */
class SendReservationRemindersUseCase
{
    /** @var array<int, array{project: ReservationReminderConfig, stores: array<int, ReservationReminderConfig>}> */
    private array $configs = [];

    public function __construct(
        private readonly ReservationReminderRepository $reminders,
        private readonly ReservationReminderPolicy $policy,
        private readonly ReservationSettings $settings,
        private readonly FeatureSettings $features,
        private readonly PersonalAnnouncementSender $announcements,
    ) {}

    /** @return int 送った件数 */
    public function handle(): int
    {
        $this->configs = [];
        $now = CarbonImmutable::now();
        $sent = 0;
        $until = $now->modify('+'.ReservationReminderPolicy::MAX_HOURS.' hours');
        foreach ($this->reminders->findUnremindedUntil($until, $this->settings->getProjectIdsWithReservationReminders()) as $candidate) {
            $config = $this->configFor($candidate);
            // 条件付き更新で処理権を取り、重複実行でも1回だけ送る
            $claimed = array_values(array_filter(
                $this->policy->dueStages($candidate, $now, $config),
                fn (string $stage) => $this->reminders->markReminded($candidate->reservationId, $stage, $candidate->reservedAt),
            ));
            if ($claimed === [] || $candidate->memberId === null) {
                continue;
            }
            try {
                $this->announcements->send($candidate->projectId, $candidate->memberId, 'ご予約のリマインド', $this->policy->message($candidate, $config->message), '/reservation', '予約を確認する');
                $sent++;
            } catch (\Throwable $e) {
                // 1件の失敗で他の予約を止めず、この予約は次の実行で送り直す
                foreach ($claimed as $stage) {
                    $this->reminders->unmarkReminded($candidate->reservationId, $stage);
                }
                LogHelper::error('[ReservationReminder] send failed', ['project_id' => $candidate->projectId, 'reservation_id' => $candidate->reservationId, 'error' => $e->getMessage()]);
            }
        }

        return $sent;
    }

    /** 複数店舗では予約設定を店舗ごとに上書きできるため、予約の店舗の設定を使う */
    private function configFor(ReservationReminderCandidate $candidate): ReservationReminderConfig
    {
        $projectId = $candidate->projectId;
        if (! isset($this->configs[$projectId])) {
            // リマインドはお知らせで届けるため、予約とお知らせの両方が有効なプロジェクトだけに送る
            $enabled = $this->features->isFeatureEnabled('feature_reservations_enabled', $projectId)
                && $this->features->isFeatureEnabled('feature_announcements_enabled', $projectId);
            $this->configs[$projectId] = [
                'project' => $enabled ? $this->settings->getReservationReminderConfig($projectId) : ReservationReminderConfig::disabled(),
                'stores' => $enabled ? $this->settings->getReservationReminderConfigsByStore($projectId) : [],
            ];
        }
        $configs = $this->configs[$projectId];

        return $candidate->storeId !== null ? ($configs['stores'][$candidate->storeId] ?? $configs['project']) : $configs['project'];
    }
}
