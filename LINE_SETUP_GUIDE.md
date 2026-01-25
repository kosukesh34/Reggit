# LINE Developers Console 設定ガイド

このガイドでは、LINE Developers Consoleで設定すべきURLを明確に説明します。

## 📋 現在の公開URL

ngrokの公開URLを確認してください：

```bash
./get-ngrok-url.sh
```

現在の公開URL例：
```
https://unadministrable-idiocratically-annalee.ngrok-free.dev
```

## 🔧 設定が必要な項目

### 1. LIFFアプリのエンドポイントURL

**設定場所**: LINE Developers Console → チャネル → LIFFタブ

**設定するURL**:
```
https://unadministrable-idiocratically-annalee.ngrok-free.dev/liff
```

**手順**:
1. https://developers.line.biz/console/ にアクセス
2. プロバイダーを選択
3. チャネルを選択（Messaging APIチャネル）
4. 左メニューから「LIFF」をクリック
5. LIFFアプリID `2008962886-K2CRmPwV` をクリック
6. 「エンドポイントURL」に以下を入力：
   ```
   https://unadministrable-idiocratically-annalee.ngrok-free.dev/liff
   ```
7. 「更新」ボタンをクリック

**確認方法**:
- LINEアプリで「メニュー」と送信すると、LIFFアプリが開くはずです

---

### 2. Webhook URL

**設定場所**: LINE Developers Console → チャネル → Messaging APIタブ

**設定するURL**:
```
https://unadministrable-idiocratically-annalee.ngrok-free.dev/api/line/webhook
```

**手順**:
1. https://developers.line.biz/console/ にアクセス
2. プロバイダーを選択
3. チャネルを選択（Messaging APIチャネル）
4. 左メニューから「Messaging API」をクリック
5. 「Webhook URL」セクションまでスクロール
6. 「Webhook URL」に以下を入力：
   ```
   https://unadministrable-idiocratically-annalee.ngrok-free.dev/api/line/webhook
   ```
7. 「検証」ボタンをクリック
   - ✅ 成功: 「Webhook URLの検証に成功しました」と表示される
   - ❌ 失敗: エラーメッセージを確認
8. 「Webhookの利用」を「利用する」に変更
9. 「更新」ボタンをクリック

**確認方法**:
- LINEアプリで友だち追加すると、自動的にメッセージが返信されるはずです

---

## 📝 設定チェックリスト

- [ ] ngrokが起動している（`./get-ngrok-url.sh`で確認）
- [ ] LIFFアプリのエンドポイントURLを設定
- [ ] Webhook URLを設定
- [ ] Webhook URLの検証が成功
- [ ] Webhookの利用を有効化

---

## 🔍 トラブルシューティング

### Webhook URLの検証が失敗する場合

1. **ngrokが起動しているか確認**
   ```bash
   ./get-ngrok-url.sh
   ```

2. **Webhook URLに直接アクセスして確認**
   - ブラウザで `https://your-ngrok-url.ngrok-free.dev/api/line/webhook` にアクセス
   - `{"message":"LINE Webhook endpoint is active...","status":"ok"}` が表示されればOK

3. **LINE Developers Consoleの設定を確認**
   - Webhook URLに余分なスペースや改行が入っていないか
   - `https://` で始まっているか

### LIFFアプリが開かない場合

1. **LIFFアプリのエンドポイントURLを確認**
   - ブラウザで `https://your-ngrok-url.ngrok-free.dev/liff` にアクセス
   - アプリが表示されればOK

2. **LINE Developers Consoleの設定を確認**
   - エンドポイントURLが正しく設定されているか
   - LIFFアプリが有効になっているか

---

## 📌 重要な注意事項

⚠️ **ngrokの無料プランでは、URLが再起動のたびに変更されます**

- ngrokを再起動すると、URLが変わります
- URLが変わったら、LINE Developers Consoleの設定も更新する必要があります
- 固定URLが必要な場合は、ngrokの有料プランを検討してください

---

## 🚀 次のステップ

設定が完了したら：

1. **LINEアプリでテスト**
   - 友だち追加して、自動メッセージが来るか確認
   - 「メニュー」と送信して、LIFFアプリが開くか確認

2. **商品注文のテスト**
   - LIFFアプリで商品を選択
   - カートに追加
   - 注文を完了

3. **管理画面で確認**
   - `https://your-ngrok-url.ngrok-free.dev/admin` にアクセス
   - 注文や会員情報を確認

