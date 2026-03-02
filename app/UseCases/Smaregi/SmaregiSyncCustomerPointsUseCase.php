<?php

namespace App\UseCases\Smaregi;

use App\Services\Smaregi\SmaregiApiService;

/**
 * こちらのアプリで扱ったポイント変動をスマレジ会員に送信する。
 * スマレジの会員IDが分かっている場合に使用する。
 */
class SmaregiSyncCustomerPointsUseCase
{
    private SmaregiApiService $smaregiApiService;

    public function __construct(SmaregiApiService $smaregiApiService)
    {
        $this->smaregiApiService = $smaregiApiService;
    }

    /**
     * 指定プロジェクトに紐づく契約の会員ポイントを加算・減算する。
     *
     * @param  int  $projectId  プロジェクトID
     * @param  string  $smaregiCustomerId  スマレジ会員ID（会員一覧取得で取得した customerId）
     * @param  int  $pointDelta  加算するポイント（負の値で減算）
     * @param  int|null  $mileDelta  加算するマイル（任意）
     * @param  string|null  $pointExpireDate  ポイント失効予定日 Y-m-d（任意）
     * @return array{customerId?: string, point?: string, mile?: string, transactionHeadIds?: array}|null 成功時はレスポンス、失敗時は null
     */
    public function execute(
        int $projectId,
        string $smaregiCustomerId,
        int $pointDelta,
        ?int $mileDelta = null,
        ?string $pointExpireDate = null
    ): ?array {
        $contractId = $this->smaregiApiService->getContractIdForProject($projectId);
        if ($contractId === null || $contractId === '') {
            return null;
        }

        return $this->smaregiApiService->addCustomerPoints(
            $contractId,
            $smaregiCustomerId,
            $pointDelta,
            $mileDelta,
            $pointExpireDate
        );
    }
}
