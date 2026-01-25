# LIFFアプリのエンドポイントURL取得方法

## クイックスタート

### 1. ngrokを起動

```bash
# 認証トークンを設定（初回のみ）
./start-ngrok.sh

# ngrokを起動
docker-compose up -d ngrok
```

### 2. 公開URLを取得

```bash
./setup-liff.sh
```

このスクリプトが自動的にngrokの公開URLを取得し、LIFFアプリのエンドポイントURLを表示します。

### 3. 手動でURLを確認する場合

ngrokダッシュボードにアクセス：
```
http://localhost:4040
```

「Forwarding」セクションでHTTPSのURLを確認してください。
例: `https://xxxx-xxxx-xxxx.ngrok-free.app`

LIFFアプリのエンドポイントURLは：
```
https://xxxx-xxxx-xxxx.ngrok-free.app/liff
```

## LINE Developers Consoleでの設定

### LIFFアプリの設定

1. https://developers.line.biz/console/ にアクセス
2. プロバイダーとチャネルを選択
3. 「LIFF」タブを開く
4. LIFFアプリID `2008962886-K2CRmPwV` を選択（または新規作成）
5. エンドポイントURLに以下を設定：
   ```
   https://your-ngrok-url.ngrok-free.app/liff
   ```
6. 設定を保存

### Webhook URLの設定

1. 「Messaging API」タブを開く
2. Webhook URLに以下を設定：
   ```
   https://your-ngrok-url.ngrok-free.app/api/line/webhook
   ```
3. Webhookの利用を有効化

## エンドポイント一覧

### LIFFアプリ
- `GET /liff` - LIFFアプリのメインページ
- `GET /liff/products` - 商品一覧API
- `GET /liff/member` - 会員証情報API
- `GET /liff/orders` - 注文履歴API

### その他のAPI
- `POST /api/line/webhook` - LINE Webhook
- `POST /api/orders` - 注文作成
- `POST /api/payment/create-intent` - Stripe決済

## テスト方法

1. ngrokで公開URLを取得
2. LINE Developers ConsoleでLIFFアプリのエンドポイントURLを設定
3. LINEアプリでミニアプリを開く
4. 商品を選択して注文をテスト

