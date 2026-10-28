<?php

namespace App\Domain\Survey\Repositories;

use App\Domain\Survey\ValueObjects\SurveyDefinition;

/** 会員の回答と自動の回答依頼で読むアンケート定義。 */
interface SurveyRepository
{
    public function find(int $projectId, int $surveyId): ?SurveyDefinition;

    /** @return list<SurveyDefinition> 有効なもの（期間の判定は呼び出し側） */
    public function findActive(int $projectId): array;

    /** @return list<SurveyDefinition> 全プロジェクトの、自動の回答依頼を設定した有効なもの */
    public function findActiveWithInvitation(): array;
}
