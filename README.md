# LINE モバイルオーダー & 会員証アプリ

Laravel、MySQL、Rancher Desktopを使用したLINEのモバイルオーダーアプリと会員証アプリです。

## 機能

### モバイルオーダー機能
- 商品一覧の表示
- 商品の注文
- 注文履歴の確認
- ポイントを使用した決済
- Stripe決済対応
- 購入によるポイント獲得（100円につき1ポイント）

### 会員証機能
- LINEアカウントでの会員登録
- 会員情報の管理
- ポイントの確認と履歴
- 会員番号の発行

### LINE統合
- LINE Messaging APIとの連携
- Webhookによるメッセージ受信
- リッチメッセージの送信

### 管理画面
- ダッシュボード（統計情報）
- 商品管理（追加・編集・削除）
- 注文管理（ステータス更新）
- 会員管理（ポイント履歴確認）

## 必要な環境

- Docker / Rancher Desktop
- PHP 8.2以上
- Composer
- MySQL 8.0
- ngrok（外部公開する場合）

## セットアップ

### 1. リポジトリのクローン

```bash
cd "/Users/kosuke/Desktop/Mobile Order"
```

### 2. 環境変数の設定

`env.example`をコピーして`.env`ファイルを作成し、必要な値を設定してください。

```bash
cp env.example .env
```

`.env`ファイルで以下の値を設定してください：

```
LINE_CHANNEL_ACCESS_TOKEN=your_channel_access_token
LINE_CHANNEL_SECRET=your_channel_secret
LINE_LIFF_URL=your_liff_url

STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

### 3. Dockerコンテナの起動

```bash
docker-compose up -d
```

### 4. Composerパッケージのインストール

```bash
docker-compose exec app composer install
```

### 5. アプリケーションキーの生成

```bash
docker-compose exec app php artisan key:generate
```

### 6. データベースマイグレーション

```bash
docker-compose exec app php artisan migrate
```

### 7. シーダーの実行（オプション）

サンプル商品データを投入する場合：

```bash
docker-compose exec app php artisan db:seed
```

### 8. 商品画像のダウンロード

```bash
docker-compose exec app php artisan products:download-images
```

## ngrokを使用した外部公開

### セットアップ

1. [ngrok](https://ngrok.com/) でアカウントを作成
2. [認証トークン](https://dashboard.ngrok.com/get-started/your-authtoken) を取得
3. 認証トークンを設定：

```bash
./start-ngrok.sh
```

または、`docker/ngrok/ngrok.yml` を直接編集：

```yaml
authtoken: your_auth_token_here
```

4. ngrokを起動：

```bash
docker-compose up -d ngrok
```

### 公開URLの確認

- ngrokダッシュボード: http://localhost:4040
- ログから確認: `docker-compose logs ngrok | grep 'started tunnel'`

詳細は `README_NGROK.md` を参照してください。

## LINE Developer設定

1. [LINE Developers](https://developers.line.biz/)でプロバイダーとチャネルを作成
2. Messaging APIチャネルを作成
3. Webhook URLを設定: `https://your-ngrok-url.ngrok-free.app/api/line/webhook`
4. Channel Access TokenとChannel Secretを取得して`.env`に設定
5. LIFFアプリを作成（オプション）

## アクセス方法

### フロントエンド
- ローカル: http://localhost:8080
- 外部公開: https://your-ngrok-url.ngrok-free.app

### 管理画面
- ローカル: http://localhost:8080/admin
- 外部公開: https://your-ngrok-url.ngrok-free.app/admin

## APIエンドポイント

### LINE Webhook
- `POST /api/line/webhook` - LINEからのWebhook受信

### 商品
- `GET /api/products` - 商品一覧取得
- `GET /api/products/{id}` - 商品詳細取得

### 注文
- `POST /api/orders` - 注文作成（認証必要）
- `GET /api/orders` - 注文一覧取得（認証必要）
- `GET /api/orders/{id}` - 注文詳細取得（認証必要）

### 決済
- `POST /api/payment/create-intent` - Stripe決済Intent作成（認証必要）
- `POST /api/payment/confirm` - 決済確認（認証必要）
- `POST /api/payment/webhook` - Stripe Webhook

### 会員
- `GET /api/members/me` - 会員情報取得（認証必要）
- `POST /api/members/register` - 会員登録（認証必要）
- `GET /api/members/points` - ポイント履歴取得（認証必要）
- `POST /api/members/points/add` - ポイント追加（認証必要）

## 認証

APIリクエストには以下のヘッダーが必要です：

```
X-Line-User-Id: {LINE_USER_ID}
```

または、Sanctumトークンを使用してください。

## データベース構造

- `users` - LINEユーザー情報
- `members` - 会員情報
- `products` - 商品情報
- `orders` - 注文情報
- `order_items` - 注文明細
- `point_transactions` - ポイント取引履歴

## 開発

### ログの確認

```bash
docker-compose logs -f app
```

### データベースへの接続

```bash
docker-compose exec db mysql -u mobile_order_user -p mobile_order
```

### コンテナの停止

```bash
docker-compose down
```

### データベースのリセット

```bash
docker-compose down -v
docker-compose up -d
docker-compose exec app php artisan migrate --seed
```

## ライセンス

MIT License
