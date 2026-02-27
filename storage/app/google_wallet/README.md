# Google Wallet 認証情報

このフォルダに **Google Cloud サービスアカウントの秘密鍵 JSON** を配置してください。

## やること（1つ）

**このフォルダに `credentials.json` という名前でファイルを置く。**

- 中身: Google Cloud Console → 対象サービスアカウント → 「キー」→「鍵を追加」→「新しい鍵を作成」→ **JSON** でダウンロードしたファイルをそのまま使う
- ファイル名を変えたい場合は .env の `GOOGLE_WALLET_CREDENTIALS_PATH` でフルパスを指定

## パスの例

- デフォルト（このフォルダ）: `storage/app/google_wallet/credentials.json`  
  実際の絶対パス例: プロジェクトルート/storage/app/google_wallet/credentials.json  
  Docker 内の例: `/var/www/html/storage/app/google_wallet/credentials.json`

詳細は [docs/GOOGLE_WALLET_SETUP.md](../../../docs/GOOGLE_WALLET_SETUP.md) を参照してください。
