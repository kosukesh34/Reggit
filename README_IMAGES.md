# 商品画像の管理

## 画像のダウンロード

商品画像は外部URLから自動的にダウンロードされ、ローカルストレージに保存されます。

### 画像をダウンロードするコマンド

```bash
docker-compose exec app php artisan products:download-images
```

このコマンドは以下の処理を行います：
1. データベース内の全商品の画像URLを取得
2. 各画像を `storage/app/public/images/products/` にダウンロード
3. ファイル名は `product_{商品ID}.{拡張子}` の形式
4. データベースの `image_url` をローカルパスに更新

### 画像の保存場所

- **保存先**: `storage/app/public/images/products/`
- **公開URL**: `/storage/images/products/{filename}`
- **シンボリックリンク**: `public/storage` → `storage/app/public`

### 新しい商品を追加する場合

1. 商品をデータベースに追加（外部URLで `image_url` を設定）
2. 以下のコマンドで画像をダウンロード：
   ```bash
   docker-compose exec app php artisan products:download-images
   ```

### 注意事項

- 画像は一度ダウンロードされると、データベースの `image_url` がローカルパスに更新されます
- 既にローカルパスの場合はスキップされます
- 画像のダウンロードに失敗した場合は、エラーメッセージが表示されます

