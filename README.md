# LINE Mobile Order and Membership Card Application

Laravel, MySQL, Dockerを使用したLINEモバイルオーダーアプリと会員証アプリです。

## セットアップ

1. Docker Composeで起動
```bash
docker-compose up -d
```

2. 依存関係のインストール
```bash
docker-compose exec app composer install
```

3. 環境変数の設定
```bash
cp .env.example .env
docker-compose exec app php artisan key:generate
```

4. データベースのマイグレーション
```bash
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

5. ストレージリンクの作成
```bash
docker-compose exec app php artisan storage:link
```

## 起動

```bash
docker-compose up -d
```

アプリケーションは http://localhost:8080 でアクセス可能です。

## 環境変数

`.env`ファイルに以下の設定が必要です：

- `LINE_CHANNEL_ACCESS_TOKEN`
- `LINE_CHANNEL_SECRET`
- `LINE_LIFF_URL`
- `STRIPE_KEY`
- `STRIPE_SECRET`
- `STRIPE_WEBHOOK_SECRET`
