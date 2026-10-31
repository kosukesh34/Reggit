<?php

namespace App\UseCases\Admin\Settings;

use App\Models\AdminUser;
use App\Models\StoreAppCredential;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IssueStoreAppEnrollmentUseCase
{
    /**
     * 店舗端末アプリの登録コードを発行し直す。旧端末のトークンは失効させる。
     *
     * @return string 端末に入力する登録コード（保存するのはハッシュのみ）
     */
    public function handle(AdminUser $admin, int $projectId, int $storeId, string $app): string
    {
        $plainToken = Str::upper(Str::random(8).'-'.Str::random(8).'-'.Str::random(8));
        DB::transaction(function () use ($admin, $projectId, $storeId, $app, $plainToken): void {
            $credential = StoreAppCredential::query()->firstOrNew([
                'project_id' => $projectId,
                'store_id' => $storeId,
                'app' => $app,
            ]);
            if ($credential->personal_access_token_id !== null) {
                DB::table('personal_access_tokens')->where('id', $credential->personal_access_token_id)->delete();
            }
            $credential->fill([
                'admin_user_id' => $admin->id,
                'personal_access_token_id' => null,
                'enrollment_token_hash' => hash('sha256', $plainToken),
                'device_key_hash' => null,
                'device_name' => null,
                'enrollment_created_at' => now(),
                'claimed_at' => null,
            ])->save();
        });

        return $plainToken;
    }
}
