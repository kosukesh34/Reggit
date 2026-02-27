# Google Wallet 会員証 セットアップ手順

Android などで会員証を Google Wallet に追加する機能を有効にする手順です。

## 前提

- Google Pay & Wallet Console で Issuer を登録済みで、**Issuer ID** を取得していること
- 本アプリの Google Wallet 実装（API・画面）は済んでいます。ここでは **Console と環境変数** の設定のみ説明します

## 1. 依存パッケージのインストール

JWT 署名に `firebase/php-jwt` を使います。**PHP 8.2 以上の環境**で実行してください。

```bash
# ローカルに PHP 8.2 以上がある場合
composer install
```

Docker 利用時はコンテナ内で実行します。`composer.lock` に `firebase/php-jwt` がまだ無い場合は、先に以下で lock を更新してから `composer install` します。

```bash
docker compose exec app composer update firebase/php-jwt --no-interaction
docker compose exec app composer install --no-interaction
```

## 2. Google Cloud サービスアカウントの準備

1. [Google Cloud Console](https://console.cloud.google.com/) で、Pay & Wallet に使うプロジェクトを選択
2. **IAM と管理** → **サービスアカウント** → **サービスアカウントを作成**
3. 名前（例: `wallet-issuer`）を入力して作成
4. 作成したサービスアカウントの **キー** タブ → **キーを追加** → **新しいキーを作成** → **JSON** を選択してダウンロード
5. ダウンロードした JSON を **`storage/app/google_wallet/credentials.json`** に配置  
   - ファイル名は `.env` の `GOOGLE_WALLET_CREDENTIALS_PATH` で変更可能

**注意:** `credentials.json` は秘密鍵を含むため、リポジトリにコミットしないでください。`storage/app/google_wallet/.gitignore` で除外済みです。

## 3. Google Pay & Wallet Console の設定

1. [Google Pay & Wallet Console](https://pay.google.com/business/console) にログイン
2. 対象の **Issuer** を選択
3. **App permissions**（アプリの権限）で、「Add to Google Wallet」を表示してよい **オリジン（URL）** を追加  
   - 例: `https://あなたのドメイン.com`, `https://liff.line.me`, `https://localhost:8080`（ローカル確認用）
4. **Issuer ID** を控える（例: `3388000000023085556`）。次の .env で使います

Smart Tap は、NFC 端末でかざして使う場合のみ必要です。会員証を「追加してバーコード表示」だけなら設定不要です。

## 4. .env の設定

`.env` に以下を追加または編集します。

```env
# Google Wallet を有効にする
GOOGLE_WALLET_ENABLED=true

# Pay & Wallet Console の Issuer ID（数値）
GOOGLE_WALLET_ISSUER_ID=3388000000023085556

# サービスアカウント JSON のパス（通常はこのままで可）
GOOGLE_WALLET_CREDENTIALS_PATH=storage/app/google_wallet/credentials.json

# 「Add to Google Wallet」を表示してよいオリジン（カンマ区切り）
# Console に登録したドメインと一致させる
GOOGLE_WALLET_ORIGINS=https://あなたのドメイン.com,https://liff.line.me

# 会員証クラスのサフィックス（通常は変更不要）
GOOGLE_WALLET_CLASS_SUFFIX=membership
```

- `GOOGLE_WALLET_ISSUER_ID`: Console で表示されている Issuer ID に置き換えてください
- `GOOGLE_WALLET_ORIGINS`: 実際にボタンやリンクを出すドメインを、Console に登録したものと揃えてください

### オリジン（URL）の決め方

**オリジン** = 「Add to Google Wallet」のボタンやリンクを表示する**ページの出どころ**です。  
`https://` または `http://` + **ホスト名** + 必要なら **`:ポート`** まで。パス（`/liff/...`）は含めません。

| 使う場所 | オリジン例 |
|----------|------------|
| 本番のアプリ（あなたのドメイン） | `https://あなたのドメイン.com`（ポート 443 の場合は省略可） |
| ローカルでテスト（Docker など） | `http://localhost:8080` または `https://localhost:8080` |
| LINE 内ブラウザで開くページがあなたのドメインの場合 | 上と同じく `https://あなたのドメイン.com` |
| LIFF の公式ドメインで開く場合 | `https://liff.line.me` |

**.env と Console で同じにする**  
`.env` の `GOOGLE_WALLET_ORIGINS` と、Pay & Wallet Console の **App permissions** に登録する URL は、**完全に同じ**にしてください（スキーム・ホスト・ポートまで一致）。

例（本番 + ローカルテスト）:
```env
GOOGLE_WALLET_ORIGINS=https://mobile-order.example.com,http://localhost:8080
```

## 5. 動作確認

### 会員証画面から（推奨）

1. LINE で LIFF アプリ（会員証タブ）を **Android 端末**で開く
2. 「Google Walletに追加」ボタンが表示される
3. タップ → API で URL を取得 → Google Wallet の保存画面に遷移して追加

### API で URL 取得（開発・検証用）

認証済み（セッションまたは token）で次の API を呼ぶと、Add to Google Wallet 用 URL が返ります。

```
GET /api/members/me/google-wallet-url?project_key=default
```

（token の場合は `&token=発行されたトークン` を付与）

## トラブルシューティング

| 現象 | 確認すること |
|------|----------------|
| ボタンが表示されない | `GOOGLE_WALLET_ENABLED=true`、`GOOGLE_WALLET_ORIGINS` にそのドメインが含まれているか、Console の App permissions にオリジン登録済みか |
| 「URL を発行できませんでした」 | 会員登録済みか、`credentials.json` のパスと中身（client_email, private_key）が正しいか |
| 保存時にエラー | JWT の `origins` と Console の登録が一致しているか、Issuer ID が正しいか |

## 参考

- [Google Wallet API（Loyalty）](https://developers.google.com/wallet/retail/loyalty-cards/overview)
- [Add to Google Wallet リンク（JWT）](https://developers.google.com/wallet/retail/loyalty-cards/use-cases/jwt)
