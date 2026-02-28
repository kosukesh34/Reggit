# Google Wallet 会員証 — 不足しているもの・やること一覧

「何が足りないか分からない」ためのチェックリストです。**上から順に**やれば、Google Wallet に会員証を追加できる状態になります。

---

## すでにできていること（コード側）

- ✅ API・設定ファイルの実装は済んでいます
- ✅ 会員証画面の「Google Walletに追加」ボタン（Android 端末で表示。タップで API から URL を取得し Google Wallet の保存画面へ遷移）
- ✅ 設定例は `.env.example` に記載済み

**あなたがやるのは、次の「やること」だけです。**

---

## やること一覧（不足しているもの）

### ① Composer でパッケージを入れる

Google Wallet の JWT 署名に使うライブラリを入れます。

**Docker で動かしている場合:**

```bash
docker compose exec app composer update firebase/php-jwt --no-interaction
docker compose exec app composer install --no-interaction
```

**ローカルに PHP 8.2 以上がある場合:**

```bash
composer update firebase/php-jwt --no-interaction
composer install --no-interaction
```

- **確認:** `vendor/firebase/php-jwt` フォルダが存在すれば OK

---

### ② 「秘密の鍵が書かれた JSON ファイル」を 1 つ作って、プロジェクトに置く

**なぜ必要か:**  
Google Wallet に「この会員証は正しいです」と証明するために、Google が「この鍵を持っている人だけが発行できる」という形で使うファイルです。このファイルをアプリから読んで、会員証のデータに署名しています。

**やることの流れ:**  
Google の画面で「鍵」を発行してもらい、ダウンロードしたファイルを**プロジェクトの決まった場所**に保存するだけです。

---

**ステップ 1: Google Cloud の画面を開く**

- ブラウザで **[https://console.cloud.google.com/](https://console.cloud.google.com)** を開く
- Google アカウントでログインする
- 画面上方の「プロジェクトを選択」で、**Pay & Wallet で使う（またはこれから作る）プロジェクト**を選ぶ

---

**ステップ 2: サービスアカウントを作る**

- 左側のメニュー（≡）を開く
- **「IAM と管理」** をクリック → その中から **「サービスアカウント」** をクリック
- 画面の **「＋ サービスアカウントを作成」** をクリック
- **サービスアカウント名** に好きな名前を入れる（例: `wallet-issuer`）。他は空でよい
- **「作成して続行」** → **「完了」** まで進める

これで「このプロジェクト用の、プログラム用のアカウント」が 1 つできます。

---

**ステップ 3: 鍵（JSON ファイル）をダウンロードする**

- 一覧に、今作ったサービスアカウント（例: wallet-issuer）が出ているので、**その行をクリック**する
- 上の方にある **「キー」** というタブをクリック
- **「キーを追加」** → **「新しいキーを作成」** をクリック
- **キーのタイプ** で **「JSON」** にチェックが入っていることを確認して **「作成」** をクリック
- すると **1 個の JSON ファイルがパソコンにダウンロード**されます（名前は長い英数字です）

このファイルの中に、**秘密の鍵**が入っています。**人に渡したり、Git に上げたりしないでください。**

---

**ステップ 4: そのファイルをプロジェクトの「決まった場所」に置く**

1. **プロジェクトのフォルダ**（`mobile-order-app` がある場所）を開く
2. その中に **`storage`** → **`app`** があるので、さらに **`google_wallet`** というフォルダを作る（まだなければ）
   - 結果、`mobile-order-app/storage/app/google_wallet/` というパスになる
3. ステップ 3 でダウンロードした JSON ファイルを、**名前を `credentials.json` に変えて**、この `google_wallet` フォルダの中に置く

**置いたあとのパス（例）:**  
`mobile-order-app/storage/app/google_wallet/credentials.json`

- **確認:**  
  その `credentials.json` をメモ帳などで開くと、`"client_email"` と `"private_key"` という文字列が中に出てくれば OK です。

---

### ②のあと: Google Cloud で「Google Wallet API」を有効にする

**なぜ必要か:**  
credentials.json を作成した **Google Cloud プロジェクト**で、**Google Wallet API** が有効になっていないと、クラス作成や会員証の発行が 403 エラーになります。

**やること:**

1. **[Google Cloud Console - API とサービス - ライブラリ](https://console.cloud.google.com/apis/library)** を開く
2. 画面上方の「プロジェクトを選択」で、**credentials.json を作成したプロジェクト**（サービスアカウントがあるプロジェクト）を選ぶ
3. 検索ボックスに **「Google Wallet API」** または **「Wallet Object」** と入力する
4. **「Google Wallet API」**（walletobjects.googleapis.com）をクリック → **「有効にする」** をクリック
5. 有効化が完了するまで 1〜2 分待ってから、再度 `php artisan google-wallet:ensure-class` を実行する

**直接リンク（プロジェクト ID が 743508604873 の場合の例）:**  
[https://console.cloud.google.com/apis/api/walletobjects.googleapis.com/overview?project=743508604873](https://console.cloud.google.com/apis/api/walletobjects.googleapis.com/overview?project=743508604873)  
※ プロジェクト ID は credentials.json の `project_id` や、ログの `consumer: "projects/743508604873"` で確認できます。

- **確認:**  
  API が「有効」になっていれば OK です。

---

### ③ Google Pay & Wallet Console で「Issuer ID」と「App permissions」を設定する

1. [Google Pay & Wallet Console](https://pay.google.com/business/console) にログイン
2. 使う **Issuer** を選択（なければ作成して Issuer ID を取得）
3. **Issuer ID**（数字の ID）をメモする（例: `3388000000023085556`）
4. **App permissions**（または「許可するオリジン」のような項目）を開く
5. 次の **オリジン（URL）** を 1 つずつ追加する（ローカルで試す場合の例）:
   - `http://localhost:8080`
   - 本番で使う場合は `https://あなたのドメイン.com` も追加

- **確認:**  
  Console に、あとで .env に書くのと**同じ文字列**のオリジンが登録されていること

---

### ④ .env に Google Wallet 用の設定を書く

プロジェクトの **`.env`** を開き、次の 5 項目を追加または編集する。

| 項目 | 例（ローカルで試す場合） | 説明 |
|------|--------------------------|------|
| `GOOGLE_WALLET_ENABLED` | `true` | 有効にする |
| `GOOGLE_WALLET_ISSUER_ID` | `3388000000023085556` | ③でメモした Issuer ID |
| `GOOGLE_WALLET_CREDENTIALS_PATH` | `storage/app/google_wallet/credentials.json` | ②で置いた JSON のパス |
| `GOOGLE_WALLET_ORIGINS` | `http://localhost:8080` | ③で登録したのと**同じ**オリジン（複数はカンマ区切り） |
| `GOOGLE_WALLET_CLASS_SUFFIX` | `membership` | 通常は変更不要 |

**ローカルで試すときの .env の例（抜粋）:**

```env
GOOGLE_WALLET_ENABLED=true
GOOGLE_WALLET_ISSUER_ID=3388000000023085556
GOOGLE_WALLET_CREDENTIALS_PATH=storage/app/google_wallet/credentials.json
GOOGLE_WALLET_ORIGINS=http://localhost:8080
GOOGLE_WALLET_CLASS_SUFFIX=membership
```

- **確認:**  
  - Issuer ID が Console のものと一致している  
  - ORIGINS が Console に登録した URL と**完全に同じ**（`http`/`https`、ポート番号も含む）

---

### ⑤ 動作確認する

1. アプリを起動する（例: `docker compose up -d`）
2. LIFF の会員証タブを **Android 端末（または Android エミュレータ）** で開き、会員としてログインする
3. 「Google Walletに追加」ボタンが表示され、タップすると Google Wallet の保存画面に遷移すれば OK

- **うまくいかないとき:**  
  [GOOGLE_WALLET_SETUP.md](GOOGLE_WALLET_SETUP.md) の「トラブルシューティング」を参照

---

## Google の画面で「エラーが発生しました」「もう一度お試しください」と出るとき

**Add to Google Wallet を押したあと、Google（pay.google.com）のページに飛んでからこのメッセージが出る**場合は、**Google 側**で JWT や設定が拒否されています。当アプリの画面ではなく、Google が表示しているエラーです。

**よくある原因と対処:**

| 原因 | 対処 |
|------|------|
| **サービスアカウントが Issuer にリンクされていない** | [Pay & Wallet Console](https://pay.google.com/business/console) → 対象の **Issuer** → **「サービスアカウント」や「Permissions」** の項目を開く → **credentials.json の `client_email`**（例: `xxx@yyy.iam.gserviceaccount.com`）を追加して保存する。 |
| **オリジンが一致していない** | 実際にブラウザで開いている URL のオリジン（例: `https://xxx.ngrok-free.dev` や `http://localhost:8080`）を、① Console の **App permissions** に登録し、② .env の **GOOGLE_WALLET_ORIGINS** に**同じ文字列**で追加する。 |
| **Issuer ID の打ち間違い** | .env の **GOOGLE_WALLET_ISSUER_ID** が、Pay & Wallet Console に表示されている Issuer ID と完全に一致しているか確認する。 |

- **確認のコツ:** ブラウザのアドレスバーに表示されている「今開いている URL」の **オリジン部分だけ**（`https://` または `http://` + ホスト名 + ポートがあれば `:8080` など。末尾の `/` やパスは含めない）を、Console の **App permissions** に **1 件ずつ追加**する。例: `https://xxx.ngrok-free.dev` や `http://localhost:8080`。
- **JWT の origins:** 当アプリは、リダイレクト時に「今のリクエストのオリジン」を JWT に自動で含めます。`storage/logs/laravel.log` で `google-wallet JWT origins` を検索すると、実際に JWT に含めたオリジン一覧が確認できます。**そのいずれかが Console に登録されていれば** Google は受け付けます。

**上記を確認してもまだ pay.google.com でエラーになる場合:**

公式仕様では「class must already exist, and must be approved」とあるため、**REST で Class を先に作成**すると安定することがあります。

1. 次のコマンドで LoyaltyClass を 1 回だけ作成する:
   ```bash
   php artisan google-wallet:ensure-class
   ```
   （プロジェクト名をクラスに反映したい場合は `--project_key=default` のように指定）
2. [Pay & Wallet Console](https://pay.google.com/business/console) で対象 Issuer を開き、作成されたクラスが **UNDER_REVIEW → APPROVED** になるのを確認する（自動承認される場合あり）。
3. 再度「Add to Google Wallet」から保存を試す（アプリはクラスが存在すれば自動で Object のみ送ります）。

---

## まとめ：不足しているのはこの 5 つだけ

| # | やること | 状態を確認する方法 |
|---|----------|--------------------|
| ① | `firebase/php-jwt` を Composer でインストール | `vendor/firebase/php-jwt` がある |
| ② | サービスアカウントの JSON を `storage/app/google_wallet/credentials.json` に置く | ファイルが存在し、中身に `private_key` がある |
| ②のあと | Google Cloud で **Google Wallet API** を有効にする | Cloud Console の API ライブラリで「有効」になっている |
| ③ | Pay & Wallet Console で Issuer ID をメモし、App permissions にオリジン登録 | Console にオリジンが登録されている |
| ④ | .env に 5 項目（ENABLED, ISSUER_ID, CREDENTIALS_PATH, ORIGINS, CLASS_SUFFIX）を書く | 値が Console・ファイルパスと一致している |

①〜④と「②のあと」がすべてできていれば、**不足しているものはありません。** ⑤の手順で会員証画面から動作確認してください。

---

## Apple Wallet も使う場合（参考）

Google Wallet とは別に、Apple Wallet（.pkpass）を使う場合は以下も必要です。

- Apple Developer で Pass Type ID と証明書を用意する
- `.env` に `WALLET_PASS_ENABLED=true` および `WALLET_PASS_TEAM_ID`, `WALLET_PASS_TYPE_ID`, 証明書・鍵のパスを設定する

詳細は `.env.example` の `WALLET_PASS_*` のコメントを参照してください。
