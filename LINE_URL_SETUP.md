# LINE Developers Console 設定URL

## 📋 現在のngrok URL

```bash
./get-ngrok-url.sh
```

現在の公開URL:
```
https://unadministrable-idiocratically-annalee.ngrok-free.dev
```

---

## 🔧 LINE Developers Consoleで設定するURL

### 1. LIFFアプリのエンドポイントURL

**設定場所**: LINE Developers Console → チャネル → **LIFF**タブ

**設定するURL**:
```
https://unadministrable-idiocratically-annalee.ngrok-free.dev/liff
```

**手順**:
1. https://developers.line.biz/console/ にアクセス
2. プロバイダーを選択
3. チャネルを選択（Messaging APIチャネル）
4. 左メニューから「**LIFF**」をクリック
5. LIFFアプリID `2008962886-K2CRmPwV` をクリック
6. 「**エンドポイントURL**」に上記URLを入力
7. 「**更新**」ボタンをクリック

---

### 2. Webhook URL

**設定場所**: LINE Developers Console → チャネル → **Messaging API**タブ

**設定するURL**:
```
https://unadministrable-idiocratically-annalee.ngrok-free.dev/api/line/webhook
```

**手順**:
1. https://developers.line.biz/console/ にアクセス
2. プロバイダーを選択
3. チャネルを選択（Messaging APIチャネル）
4. 左メニューから「**Messaging API**」をクリック
5. 下にスクロールして「**Webhook URL**」セクションを見つける
6. 「**Webhook URL**」に上記URLを入力
7. 「**検証**」ボタンをクリック
   - ✅ 成功: 「Webhook URLの検証に成功しました」と表示される
8. 「**Webhookの利用**」を「**利用する**」に変更
9. 「**更新**」ボタンをクリック

---

## ⚠️ 重要な注意事項

**ngrokを再起動するとURLが変わります！**

URLが変わったら：
1. `./get-ngrok-url.sh` で新しいURLを確認
2. 上記2つの設定を新しいURLに更新

---

## 🧪 動作確認

### LIFFアプリの確認
- LINEアプリで「**メニュー**」と送信
- LIFFアプリが開けばOK ✅

### Webhookの確認
- LINEアプリで友だち追加
- 自動的にメッセージが返ってくればOK ✅

---

## 📝 その他のエンドポイントURL

- **フロントエンド**: `https://unadministrable-idiocratically-annalee.ngrok-free.dev/`
- **管理画面**: `https://unadministrable-idiocratically-annalee.ngrok-free.dev/admin`
- **API**: `https://unadministrable-idiocratically-annalee.ngrok-free.dev/api`

