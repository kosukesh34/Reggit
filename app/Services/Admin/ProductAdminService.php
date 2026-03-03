<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Models\Scopes\ProjectScope;
use App\Services\Smaregi\SmaregiProductRegisterService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductAdminService
{
    public function __construct(
        private SmaregiProductRegisterService $smaregiProductRegister
    ) {}

    public function getPaginatedList(int $perPage = 20): LengthAwarePaginator
    {
        return Product::orderByDesc('id')->paginate($perPage);
    }

    /**
     * 商品作成。スマレジ契約ありの場合は先にPOSへ登録し、成功時のみDBに保存する。
     * API が失敗した場合は RuntimeException を投げ、DB には保存しない。
     */
    public function create(int $projectId, array $data): Product
    {
        $data['project_id'] = $projectId;

        if ($this->smaregiProductRegister->hasSmaregiContract($projectId)) {
            $result = $this->smaregiProductRegister->registerToSmaregi($projectId, $data);
            if ($result === null) {
                throw new \RuntimeException('スマレジへの商品登録に失敗しました。契約ID・スコープ(pos.products:write)・部門の登録・ログ(storage/logs/laravel.log)を確認してください。');
            }
            $data['smaregi_product_id'] = $result['smaregi_product_id'];
            if (array_key_exists('stock', $data)) {
                $this->smaregiProductRegister->syncStockToSmaregi(
                    $projectId,
                    $result['smaregi_product_id'],
                    (int) $data['stock']
                );
            }
        }

        return Product::create($data);
    }

    public function findOrFail(int $id): Product
    {
        return Product::findOrFail($id);
    }

    /** 指定プロジェクトに属する商品を取得。存在しない場合は ModelNotFoundException（404） */
    public function findForProjectOrFail(int $projectId, int $id): Product
    {
        return Product::withoutGlobalScope(ProjectScope::class)
            ->where('project_id', $projectId)
            ->findOrFail($id);
    }

    /**
     * 商品更新。スマレジ契約ありの場合は先にPOSへ更新/登録し、成功時のみDBを更新する。
     * API が失敗した場合は RuntimeException を投げ、DB は更新しない。
     */
    public function update(int $id, array $data): Product
    {
        $product = Product::findOrFail($id);
        $projectId = (int) $product->project_id;

        if ($this->smaregiProductRegister->hasSmaregiContract($projectId)) {
            $smaregiId = $product->smaregi_product_id ?? '';
            if ($smaregiId !== null && $smaregiId !== '') {
                $ok = $this->smaregiProductRegister->updateOnSmaregi($projectId, $smaregiId, $data);
                if (! $ok) {
                    throw new \RuntimeException('スマレジの商品更新に失敗しました。ログ(storage/logs/laravel.log)を確認してください。');
                }
                if (array_key_exists('stock', $data)) {
                    $this->smaregiProductRegister->syncStockToSmaregi($projectId, $smaregiId, (int) $data['stock']);
                }
            } else {
                $result = $this->smaregiProductRegister->registerToSmaregi($projectId, array_merge($data, ['project_id' => $projectId]));
                if ($result === null) {
                    throw new \RuntimeException('スマレジへの商品登録に失敗しました。契約ID・スコープ(pos.products:write)・部門の登録・ログ(storage/logs/laravel.log)を確認してください。');
                }
                $data['smaregi_product_id'] = $result['smaregi_product_id'];
                if (array_key_exists('stock', $data)) {
                    $this->smaregiProductRegister->syncStockToSmaregi(
                        $projectId,
                        $result['smaregi_product_id'],
                        (int) $data['stock']
                    );
                }
            }
        }

        $product->update($data);

        return $product;
    }

    /**
     * 商品削除。スマレジ契約ありでスマレジ連携済みの場合は先にPOSから削除し、成功時のみDBから削除する。
     */
    public function delete(int $id): void
    {
        $product = Product::findOrFail($id);
        $projectId = (int) $product->project_id;
        $smaregiId = $product->smaregi_product_id ?? '';

        if ($smaregiId !== null && $smaregiId !== '' && $this->smaregiProductRegister->hasSmaregiContract($projectId)) {
            $ok = $this->smaregiProductRegister->deleteOnSmaregi($projectId, $smaregiId);
            if (! $ok) {
                throw new \RuntimeException('スマレジの商品削除に失敗しました。ログ(storage/logs/laravel.log)を確認してください。');
            }
        }

        $product->delete();
    }
}
