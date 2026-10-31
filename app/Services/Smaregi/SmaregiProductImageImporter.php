<?php

namespace App\Services\Smaregi;

use App\Models\Product;
use App\Services\Admin\PublicImageStorageService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/** スマレジの商品画像を取り込み、商品の画像 URL を更新する（スマレジから消えた画像は削除）。 */
class SmaregiProductImageImporter
{
    private const LIMIT = 100;

    public function __construct(private SmaregiApiService $smaregiApi) {}

    /**
     * スマレジの商品画像一覧は一時URLを返すため、取り込み直後にURLを保存する。
     * API取得失敗時は既存画像を消さず、正常取得時は画像のない商品を空にして古い画像を残さない。
     *
     * @param  array<string, bool>  $smaregiProductIds
     */
    public function import(int $projectId, string $contractId, array $smaregiProductIds): void
    {
        $urlsByProductId = [];
        $page = 1;
        do {
            $images = $this->smaregiApi->request($contractId, 'GET', 'pos/products/images', [
                'limit' => self::LIMIT,
                'page' => $page,
            ]);

            if ($images === null) {
                Log::warning('[Smaregi] product image list fetch failed; existing images were kept', ['project_id' => $projectId]);

                return;
            }

            $rows = $this->extractListFromResponse($images, 'images');
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $productId = trim((string) ($row['productId'] ?? ''));
                $url = trim((string) ($row['url'] ?? ''));
                if ($productId !== '' && $url !== '' && isset($smaregiProductIds[$productId]) && $url !== 'failed') {
                    $urlsByProductId[$productId] = $url;
                }
            }
            $page++;
        } while (count($rows) === self::LIMIT);

        if ($smaregiProductIds === []) {
            return;
        }

        $products = Product::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->whereIn('smaregi_product_id', array_keys($smaregiProductIds))
            ->get(['id', 'smaregi_product_id', 'image_url']);

        foreach ($products as $product) {
            $remoteUrl = $urlsByProductId[(string) $product->smaregi_product_id] ?? null;
            if ($remoteUrl === null) {
                if ((string) $product->image_url !== '') {
                    $this->deleteStoredProductImage($projectId, (string) $product->image_url);
                    $product->update(['image_url' => '']);
                }

                continue;
            }

            try {
                $response = Http::timeout(20)->get($remoteUrl);
                if (! $response->successful() || $response->body() === '') {
                    Log::warning('[Smaregi] product image download failed', [
                        'project_id' => $projectId,
                        'smaregi_product_id' => $product->smaregi_product_id,
                        'status' => $response->status(),
                    ]);

                    continue;
                }

                $extension = $this->imageExtension($response->header('Content-Type'), $remoteUrl);
                if ($extension === null) {
                    Log::warning('[Smaregi] product image has unsupported content type', [
                        'project_id' => $projectId,
                        'smaregi_product_id' => $product->smaregi_product_id,
                        'content_type' => $response->header('Content-Type'),
                    ]);

                    continue;
                }

                $path = sprintf('%s/%d/smaregi_%s.%s', PublicImageStorageService::DIR_PRODUCT, $projectId, sha1((string) $product->smaregi_product_id), $extension);
                Storage::disk('public')->put($path, $response->body());
                $localUrl = asset('storage/'.$path);
                if ((string) $product->image_url !== $localUrl) {
                    $this->deleteStoredProductImage($projectId, (string) $product->image_url);
                    $product->update(['image_url' => $localUrl]);
                }
            } catch (\Throwable $e) {
                Log::warning('[Smaregi] product image download failed', [
                    'project_id' => $projectId,
                    'smaregi_product_id' => $product->smaregi_product_id,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    private function imageExtension(?string $contentType, string $url): ?string
    {
        $type = strtolower(trim(explode(';', (string) $contentType)[0]));
        $extension = match ($type) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            default => null,
        };

        if ($extension !== null) {
            return $extension;
        }

        $pathExtension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        return in_array($pathExtension, ['jpg', 'jpeg', 'png', 'gif'], true)
            ? ($pathExtension === 'jpeg' ? 'jpg' : $pathExtension)
            : null;
    }

    private function deleteStoredProductImage(int $projectId, string $imageUrl): void
    {
        $directory = PublicImageStorageService::DIR_PRODUCT.'/'.$projectId.'/';
        $prefix = asset('storage/'.rtrim($directory, '/')).'/';
        if (! str_starts_with($imageUrl, $prefix)) {
            return;
        }
        // Only files directly created by our image storage belong to this project.
        // Reject separators, relative components and encoded paths before Flysystem normalizes them.
        $filename = substr($imageUrl, strlen($prefix));
        if (! preg_match('/\A[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|gif|webp)\z/i', $filename)) {
            return;
        }
        Storage::disk('public')->delete($directory.$filename);
    }

    /**
     * @param  array<int|string, string|int|float|bool|array|null>  $result  API一覧応答
     * @return array<int|string, string|int|float|bool|array|null>
     */
    private function extractListFromResponse(array $result, string $listKey): array
    {
        if (array_keys($result) === range(0, count($result) - 1) || $result === []) {
            return $result;
        }
        $list = $result[$listKey] ?? [];

        return is_array($list) ? $list : [];
    }
}
