<?php

namespace App\UseCases\Onboarding\Data;

/**
 * 公開オンボーディングでのプロジェクト作成入力。検証済み。
 *
 * @param  array{
 *     project_key: string,
 *     name: string,
 *     description: string|null,
 *     is_active: bool,
 *     plan: string,
 * }  $projectPayload
 * @param  array{
 *     line_liff_id: string,
 *     line_liff_url: string,
 *     frontend_header_text: string,
 *     business_hours_start: string,
 *     business_hours_end: string,
 *     line_primary_color: string,
 *     line_primary_dark: string,
 *     line_success_color: string,
 *     line_danger_color: string,
 *     smaregi_contract_id: string,
 *     smaregi_client_id: string,
 *     smaregi_client_secret: string,
 * }  $settingsPayload
 * @param  array{
 *     login_id: string,
 *     email: string,
 *     password: string,
 *     name: string,
 * }  $adminPayload
 */
class OnboardingStoreInput
{
    /**
     * @param  array{
     *     project_key: string,
     *     name: string,
     *     description: string|null,
     *     is_active: bool,
     *     plan: string,
     * }  $projectPayload
     * @param  array{
     *     line_liff_id: string,
     *     line_liff_url: string,
     *     frontend_header_text: string,
     *     business_hours_start: string,
     *     business_hours_end: string,
     *     line_primary_color: string,
     *     line_primary_dark: string,
     *     line_success_color: string,
     *     line_danger_color: string,
     *     smaregi_contract_id: string,
     *     smaregi_client_id: string,
     *     smaregi_client_secret: string,
     * }  $settingsPayload
     * @param  array{
     *     login_id: string,
     *     email: string,
     *     password: string,
     *     name: string,
     * }  $adminPayload
     */
    public function __construct(
        public readonly array $projectPayload,
        public readonly array $settingsPayload,
        public readonly array $adminPayload,
    ) {}
}
