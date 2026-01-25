<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class DownloadProductImages extends Command
{
    protected $signature = 'products:download-images';
    protected $description = 'Download product images from URLs and save locally';

    public function handle()
    {
        $products = Product::whereNotNull('image_url')->get();
        $downloaded = 0;
        $failed = 0;

        foreach ($products as $product) {
            try {
                $imageUrl = $product->image_url;
                $extension = $this->getExtensionFromUrl($imageUrl);
                $filename = 'product_' . $product->id . '.' . $extension;
                $path = 'images/products/' . $filename;

                // 画像をダウンロード
                $response = Http::timeout(30)->get($imageUrl);
                
                if ($response->successful()) {
                    // 画像を保存
                    Storage::disk('public')->put($path, $response->body());
                    
                    // データベースを更新
                    $product->image_url = Storage::url($path);
                    $product->save();
                    
                    $this->info("Downloaded: {$product->name} -> {$filename}");
                    $downloaded++;
                } else {
                    $this->error("Failed to download: {$product->name}");
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->error("Error downloading {$product->name}: " . $e->getMessage());
                $failed++;
            }
        }

        $this->info("\nDownloaded: {$downloaded}, Failed: {$failed}");
        return 0;
    }

    private function getExtensionFromUrl($url)
    {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '';
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        // 拡張子がない場合やクエリパラメータがある場合の処理
        if (empty($extension) || strpos($extension, '?') !== false) {
            // URLから推測
            if (strpos($url, '.webp') !== false) {
                return 'webp';
            } elseif (strpos($url, '.jpg') !== false || strpos($url, '.jpeg') !== false) {
                return 'jpg';
            } elseif (strpos($url, '.png') !== false) {
                return 'png';
            }
            return 'jpg'; // デフォルト
        }
        
        return $extension;
    }
}

