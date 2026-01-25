# LINE LIFF アプリ設定ガイド

## LIFFアプリのエンドポイント

LIFFアプリは以下のURLでアクセスできます：

### ローカル環境
```
http://localhost:8080/liff
```

### ngrok経由（外部公開）
```
https://your-ngrok-url.ngrok-free.app/liff
```

## LINE Developers Consoleでの設定

### 1. LIFFアプリの作成

1. [LINE Developers Console](https://developers.line.biz/console/) にアクセス
2. プロバイダーを選択
3. チャネルを選択
4. 「LIFF」タブを開く
5. 「追加」ボタンをクリック

### 2. LIFFアプリの設定

以下の情報を入力：

- **LIFFアプリ名**: モバイルオーダー
- **サイズ**: Full
- **エンドポイントURL**: 
  - 開発環境: `http://localhost:8080/liff`
  - 本番環境: `https://your-ngrok-url.ngrok-free.app/liff`
- **スコープ**: `profile openid email`
- **ボットリンク機能**: オン

### 3. LIFF IDの確認

LIFFアプリを作成すると、LIFF IDが発行されます。
現在のLIFF ID: `2008962886-K2CRmPwV`

このIDは `resources/views/liff/index.blade.php` の以下の部分で使用されています：

```javascript
await liff.init({ liffId: '2008962886-K2CRmPwV' });
```

### 4. Webhook URLの設定

Messaging API設定で、Webhook URLを設定：

```
https://your-ngrok-url.ngrok-free.app/api/line/webhook
```

## LIFFアプリの機能

### メニュータブ
- 商品一覧の表示
- カテゴリーフィルター
- カートへの追加
- 商品の検索

### 会員証タブ
- 会員情報の表示
- ポイント残高の確認
- ポイント履歴の表示
- 会員登録

### 注文履歴タブ
- 過去の注文一覧
- 注文詳細の確認
- 注文ステータスの確認

## 動作確認

### ローカル環境でのテスト

1. ngrokを起動：
   ```bash
   ./start-ngrok.sh
   ```

2. 公開URLを取得：
   ```bash
   docker-compose logs ngrok | grep 'started tunnel'
   ```

3. LINE Developers ConsoleでLIFFアプリのエンドポイントURLを更新

4. LINEアプリでミニアプリを開く

### 注意事項

- LIFFアプリはHTTPSが必要です（ngrokを使用すると自動的にHTTPSになります）
- ローカル環境ではngrokを使用して外部公開する必要があります
- LIFF IDは環境に応じて変更してください

## トラブルシューティング

### LIFFアプリが開かない場合

- エンドポイントURLが正しく設定されているか確認
- ngrokが正常に動作しているか確認
- ブラウザのコンソールでエラーを確認

### ユーザー情報が取得できない場合

- LINE Developers Consoleでスコープが正しく設定されているか確認
- LIFF IDが正しいか確認

