# トラブルシューティングガイド

## 問題: ngrok経由で商品が表示されない

### 確認事項

1. **ブラウザのコンソールを確認**
   - F12キーを押して開発者ツールを開く
   - 「Console」タブでエラーを確認
   - 「Network」タブでAPIリクエストの状態を確認

2. **APIエンドポイントの確認**
   ```bash
   curl https://unadministrable-idiocratically-annalee.ngrok-free.dev/api/products
   ```
   - JSON形式のデータが返ってくるか確認

3. **画像の確認**
   ```bash
   curl -I https://unadministrable-idiocratically-annalee.ngrok-free.dev/storage/images/products/product_1.webp
   ```
   - HTTP 200が返ってくるか確認

### 解決方法

1. **ブラウザのキャッシュをクリア**
   - Ctrl+Shift+Delete (Windows/Linux)
   - Cmd+Shift+Delete (Mac)
   - 「キャッシュされた画像とファイル」を選択して削除

2. **ページを強制リロード**
   - Ctrl+F5 (Windows/Linux)
   - Cmd+Shift+R (Mac)

3. **JavaScriptのエラーを確認**
   - ブラウザのコンソールでエラーメッセージを確認
   - エラーが表示されている場合は、その内容を確認

4. **ngrokのURLを確認**
   ```bash
   ./get-ngrok-url.sh
   ```
   - URLが変更されていないか確認

### よくあるエラー

#### "読み込み中..." が表示され続ける
- **原因**: APIリクエストが失敗している
- **解決**: ブラウザのコンソールでエラーを確認

#### 画像が表示されない
- **原因**: 画像パスが正しくない、または画像ファイルが存在しない
- **解決**: 
  ```bash
  docker-compose exec app ls -la storage/app/public/images/products/
  ```

#### CORSエラー
- **原因**: クロスオリジンリクエストがブロックされている
- **解決**: APIルートが正しく設定されているか確認

