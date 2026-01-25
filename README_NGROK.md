# ngrokを使用した外部公開設定

ngrokを使用して、ローカル環境のアプリケーションを外部からアクセス可能にします。

## 前提条件

- ngrokがインストールされていること
- ngrokアカウントを作成済みであること

## セットアップ手順

### 1. ngrokアカウントの作成と認証トークンの取得

1. [ngrok](https://ngrok.com/) にアクセスしてアカウントを作成
2. [認証トークンページ](https://dashboard.ngrok.com/get-started/your-authtoken) で認証トークンを取得

### 2. 認証トークンの設定

環境変数として設定：

```bash
export NGROK_AUTH_TOKEN='your_auth_token_here'
```

または、`~/.ngrok2/ngrok.yml` に設定：

```yaml
authtoken: your_auth_token_here
```

### 3. ngrokの起動

```bash
./start-ngrok.sh
```

このスクリプトは以下を行います：
- 既存のngrokプロセスを確認
- 認証トークンを確認
- ngrokをバックグラウンドで起動（ポート8080を公開）
- 公開URLを表示

### 4. 公開URLの確認

```bash
./get-ngrok-url.sh
```

または、ngrokダッシュボードで確認：
```
http://localhost:4040
```

### 5. ngrokの停止

```bash
./stop-ngrok.sh
```

または：

```bash
pkill ngrok
```

## エンドポイントURL

ngrokの公開URLを取得したら、以下のエンドポイントが利用可能です：

- **LIFFアプリ**: `https://your-ngrok-url.ngrok-free.app/liff`
- **Webhook**: `https://your-ngrok-url.ngrok-free.app/api/line/webhook`
- **フロントエンド**: `https://your-ngrok-url.ngrok-free.app/`
- **管理画面**: `https://your-ngrok-url.ngrok-free.app/admin`

## LINE Developers Consoleでの設定

### LIFFアプリの設定

1. https://developers.line.biz/console/ にアクセス
2. プロバイダーとチャネルを選択
3. 「LIFF」タブを開く
4. LIFFアプリID `2008962886-K2CRmPwV` を選択
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

## トラブルシューティング

### ngrokが起動しない場合

```bash
# ngrokのバージョンを確認
ngrok version

# 認証トークンが設定されているか確認
echo $NGROK_AUTH_TOKEN

# 手動でngrokを起動してエラーを確認
ngrok http 8080
```

### 公開URLが取得できない場合

```bash
# ngrokが起動しているか確認
pgrep ngrok

# ngrok APIから直接取得
curl -s http://localhost:4040/api/tunnels | python3 -m json.tool
```

### ポート8080が使用中の場合

アプリケーションがポート8080で起動しているか確認：

```bash
docker-compose ps
curl http://localhost:8080
```

## 注意事項

- **無料プラン**: ngrokの無料プランでは、URLが再起動のたびに変更されます
- **有料プラン**: 固定URLを使用する場合は有料プランが必要です
- **セキュリティ**: 本番環境では適切な認証とセキュリティ対策を実装してください
