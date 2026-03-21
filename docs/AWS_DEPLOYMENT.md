# AWS へのデプロイ手順

このドキュメントでは、Mobile Order アプリを AWS にデプロイする手順を説明します。アカウント作成は完了している前提です。

## デプロイ方式の選択肢

| 方式 | 特徴 | 向いているケース |
|------|------|------------------|
| **Elastic Beanstalk (PHP)** | 管理が簡単・自動スケール・RDS連携が容易 | まずはここから試したい |
| **ECS (Fargate) + RDS** | 既存の Docker 構成をそのまま活かせる | コンテナ運用に慣れている |
| **Lightsail** | 低コスト・シンプル | 小規模・固定スペックで十分な場合 |

以下では **Elastic Beanstalk（PHP プラットフォーム）** を主な例として記載し、必要に応じて ECS の要点にも触れます。

---

## 事前に用意するもの（共通）

- AWS アカウント（作成済み）
- ドメイン（任意・Route 53 で取得または既存ドメインの利用）
- 本番用の環境変数（LINE・Stripe・スマレジ・DB など）

---

## 既存の Docker 環境（Reggit-env）にデプロイする

**「Docker running on 64bit Amazon Linux 2023」で環境を作った場合**（ヘルス Unknown・実行中のバージョンが「-」の状態）は、次の手順でアプリをアップロードしてデプロイできます。

### 1. RDS（MySQL）を用意する

- まだの場合は **RDS** で MySQL 8.0 のデータベースを作成し、**エンドポイント**・DB名・ユーザー・パスワードを控えておく。
- EB 環境と **同じ VPC** にし、RDS のセキュリティグループで EB 用セキュリティグループからの **3306** を許可する。

### 2. デプロイ用 zip を作る

プロジェクトルートで、**Dockerfile.eb を Dockerfile として含めた zip** を作成します。

```bash
cd /Users/kosuke/mobile-order-app

# EB 用の Dockerfile を Dockerfile としてコピー（既存の Dockerfile は上書きされるので注意）
cp Dockerfile.eb Dockerfile

# デプロイ用 zip を作成（.env は含めない）
# 重要: docker-compose.yml と .ebextensions は含めない（EB Docker では単一コンテナのみ使用）
zip -r deploy-docker.zip . \
  -x "*.git*" \
  -x "*.env" \
  -x ".env.*" \
  -x "node_modules/*" \
  -x "vendor/*" \
  -x "storage/logs/*" \
  -x "storage/framework/cache/*" \
  -x "storage/framework/sessions/*" \
  -x "storage/framework/views/*" \
  -x "tests/*" \
  -x ".phpunit.result.cache" \
  -x "docs/*" \
  -x "*.md" \
  -x "mobile/*"

# macOS で -x が効かない場合があるため、zip 内から明示的に削除する
zip -d deploy-docker.zip "docker-compose.yml" ".ebextensions/01_deploy.config" ".ebextensions/"

# ローカルで docker-compose を使う場合は元に戻す
git checkout Dockerfile
```

zip には **Dockerfile**（中身は Nginx+PHP の単一コンテナ）と **Dockerrun.aws.json**（ポート 8080 指定）が含まれます。EB がこの zip からイメージをビルドし、8080 で待ち受けます。

### 3. 環境変数を設定する

**Elastic Beanstalk** → **Reggit-env** → **設定** → **ソフトウェア** → **編集** → **環境のプロパティ** で、次のような変数を追加します。

| 名前 | 値の例 |
|------|--------|
| `APP_ENV` | production |
| `APP_DEBUG` | false |
| `APP_KEY` | （`php artisan key:generate` で生成した base64:... の値） |
| `APP_URL` | https://Reggit.ap-northeast-3.elasticbeanstalk.com（または本番ドメイン） |
| `DB_HOST` | （RDS のエンドポイント） |
| `DB_PORT` | 3306 |
| `DB_DATABASE` | reggit |
| `DB_USERNAME` | （RDS のマスターユーザー名） |
| `DB_PASSWORD` | （RDS のパスワード） |
| LINE / Stripe / スマレジ など | 必要に応じて |

保存後、**環境変数を反映させるにはアプリ（コンテナ）の再起動が必要**です。再起動手順は下記「環境変数変更後のアプリ再起動」を参照してください。

### 4. 環境変数変更後のアプリ再起動

環境のプロパティ（.env に相当する値）を追加・変更しただけのときは、zip の再デプロイは不要で、**アプリの再起動**だけで反映されます。

**どこで再起動するか**: **Elastic Beanstalk** の **Reggit-env** 環境の画面です。

#### 方法 A: AWS コンソールから

1. **AWS コンソール** → **Elastic Beanstalk** → **環境** で **Reggit-env** を選択。
2. 左メニュー **「設定」** → **「更新、モニタリング、ログ記録」** の **「編集」**。
3. 一番下の **「環境の再起動」** で **「アプリケーションの再起動」** を選び、**「適用」**。

または、環境画面右上の **「アクション」** → **「環境の再起動」** で、**アプリケーションの再起動** を選んでも同じです。

#### 方法 B: AWS CLI でコマンドから

**EB CLI の `eb restart` は多くのバージョンで使えません。** 代わりに **AWS CLI** の `restart-app-server` を使います。

```bash
aws elasticbeanstalk restart-app-server --environment-name Reggit-env
```

（AWS CLI の認証が済んでいる前提です。未設定なら `aws configure` でアクセスキーを設定してください。）

EB CLI で環境に紐づいている場合は、プロジェクトルートで `eb status` で環境名を確認してから、上記の `Reggit-env` をその名前に置き換えて実行します。

再起動後、数分でコンテナが立ち上がり、新しい環境変数が読み込まれます。

### 5. アップロードとデプロイ

1. **Reggit-env** の画面で **「アップロードとデプロイ」** をクリック。
2. **ファイル** で `deploy-docker.zip` を選択。
3. **バージョンラベル** に任意の名前（例: `v1-docker`）を入力。
4. **デプロイ** をクリック。

初回はイメージのビルドで数分かかることがあります。**イベント** タブで進行状況を確認できます。

### 6. デプロイ後のマイグレーション

コンテナ内でマイグレーションを実行する必要があります。

- **オプション A**: **Elastic Beanstalk** → **Reggit-env** → **環境** → **接続**（または **設定** → **セキュリティ** で SSH を有効化）からインスタンスに SSH し、実行中のコンテナに入って:
  ```bash
  docker exec -it $(docker ps -q) php artisan migrate --force
  ```
- **オプション B**: 次回デプロイから、`.platform/hooks/postdeploy` でマイグレーションを自動実行するスクリプトを追加する（Docker プラットフォームではフックの場所が異なる場合があるため、まずは手動で試すとよいです）。

完了後、**ドメイン**（例: `Reggit.ap-northeast-3.elasticbeanstalk.com`）にブラウザでアクセスして動作を確認してください。

---

## 方法1: Elastic Beanstalk（PHP プラットフォーム）

### 1. RDS（MySQL）の作成

1. **AWS コンソール** → **RDS** → **データベースの作成**
2. **エンジンのタイプ**: MySQL 8.0
3. **テンプレート**: 本番なら「本番稼働用」、検証なら「無料利用枠」可
4. **設定例**
   - DB 識別子: `mobile-order-db`
   - マスターユーザー名 / パスワード: 任意（安全なパスワードを設定）
   - DB 名: `reggit`
5. **接続**: パブリックアクセスを「接続する」にするか、VPC 内のみにするか選択（後述の EB と同一 VPC にすること）
6. **セキュリティグループ**: 後から EB 用のセキュリティグループから 3306 を許可する
7. 作成後、**エンドポイント**（ホスト名）を控える

### 2. Elastic Beanstalk 環境の作成

1. **Elastic Beanstalk** → **環境の作成**
2. **環境 tier**: Web サーバー環境
3. **アプリケーション名**: `mobile-order-app` など
4. **プラットフォーム**: **PHP**（プラットフォームブランチ: PHP 8.2 を選択）
5. **アプリケーションコード**: 「サンプルアプリケーションをデプロイ」でまず動作確認してもよい（後で差し替え）
6. **プリセット**: シングルインスタンス（検証用）または「高可用性」を選択
7. **設定**
   - **インスタンス**: 必要に応じてインスタンスタイプを変更（例: t3.small）
   - **容量**: 最小 1、最大 4 など
   - **スケーリングトリガー**: CPU やリクエスト数に応じてスケールするよう設定可能

### 3. RDS と EB を同じ VPC にまとめる（推奨）

- 既に EB 環境を作成している場合: **設定** → **データベース** は使わず、手動で作成した RDS のエンドポイントを `.env` に書く方が柔軟です。
- EB と RDS が **同じ VPC** になるようにする（EB のインスタンスから RDS のエンドポイントに接続できること）。
- RDS のセキュリティグループで、EB のインスタンス用セキュリティグループからの **3306** インバウンドを許可する。

### 4. ソースのデプロイ用パッケージ作成

本番用に **vendor を含めず**、EB 上で `composer install --no-dev` を実行する形が一般的です。

```bash
# プロジェクトルートで
cd /Users/kosuke/mobile-order-app

# .env は含めない（EB の「環境のプロパティ」で設定する）
zip -r deploy.zip . \
  -x "*.git*" \
  -x "*.env" \
  -x "node_modules/*" \
  -x "storage/logs/*" \
  -x "storage/framework/cache/*" \
  -x "storage/framework/sessions/*" \
  -x "storage/framework/views/*" \
  -x "tests/*" \
  -x ".phpunit.result.cache" \
  -x "docs/*" \
  -x "*.md"
```

または、**EB CLI** を使う場合:

```bash
# EB CLI のインストール（未導入の場合）
pip install awsebcli

# 初期化（初回のみ）
eb init -p php-8.2 mobile-order-app --region ap-northeast-1

# 環境作成とデプロイ
eb create production
# または既存環境へ
eb deploy
```

`.ebextensions` で Composer 実行やマイグレーションを自動化できます（後述）。

### 5. 環境変数（.env 相当）の設定

**Elastic Beanstalk** → 対象環境 → **設定** → **ソフトウェア** → **環境のプロパティ** で以下を設定します。

| 名前 | 値の例 | 備考 |
|------|--------|------|
| `APP_ENV` | production | 本番用 |
| `APP_DEBUG` | false | 本番では必ず false |
| `APP_KEY` | base64:... | `php artisan key:generate` で生成した値 |
| `APP_URL` | https://your-domain.com | 本番の URL |
| `DB_HOST` | xxx.rds.amazonaws.com | RDS のエンドポイント |
| `DB_PORT` | 3306 | |
| `DB_DATABASE` | reggit | |
| `DB_USERNAME` | マスターユーザー名 | |
| `DB_PASSWORD` | マスターパスワード | |
| `LINE_CHANNEL_ACCESS_TOKEN` | （本番の値） | |
| `LINE_CHANNEL_SECRET` | （本番の値） | |
| `LINE_LIFF_ID` | （本番の値） | |
| `STRIPE_KEY` / `STRIPE_SECRET` / `STRIPE_WEBHOOK_SECRET` | （本番の値） | |
| その他 | スマレジ・FCM・Wallet 等 | 必要に応じて |

※ 機密情報は **Secrets Manager** や **SSM Parameter Store** に格納し、EB の設定で参照する運用にするとより安全です。

### 6. デプロイ後の初期設定（EB で実行するコマンド）

SSH で EB インスタンスに入るか、**EB の「コマンド」** や **.ebextensions** で以下を実行します。

```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7. .ebextensions で自動化（任意）

プロジェクトルートに `.ebextensions/01_composer.config` を置くと、デプロイ時に Composer と Artisan を実行できます。

```yaml
# .ebextensions/01_composer.config
commands:
  01_composer_install:
    command: "cd /var/app/staging && composer install --no-dev --optimize-autoloader"
    leader_only: true
  02_artisan_migrate:
    command: "cd /var/app/staging && php artisan migrate --force"
    leader_only: true
  03_artisan_storage_link:
    command: "cd /var/app/staging && php artisan storage:link || true"
    leader_only: true
  04_artisan_optimize:
    command: "cd /var/app/staging && php artisan config:cache && php artisan route:cache && php artisan view:cache"
    leader_only: true
```

※ パスは EB の PHP プラットフォームの実際のデプロイ先（`/var/app/current` や staging）に合わせて確認・調整してください。

---

## 方法2: ECS (Fargate) で Docker 構成をデプロイ

既存の **Dockerfile** と **docker-compose**（app + nginx + db）を活かす場合、DB は RDS に寄せて、アプリは ECS で動かします。

### 手順の流れ

1. **ECR** にイメージをプッシュするためのリポジトリを作成
2. **本番用の Docker イメージ**をビルド（nginx を 1 コンテナに含めるか、マルチタスクにするか選択）
   - シンプルにするなら「nginx + php-fpm を 1 イメージにまとめた Dockerfile」を 1 本用意する方法が運用しやすいです
3. **RDS（MySQL）** を上記と同様に作成
4. **ECS クラスター** → **タスク定義**（Fargate）でコンテナと環境変数を定義
5. **ALB** を作成し、ECS サービスでターゲットグループに登録
6. **ECS サービス** の作成（ALB と連携）

### イメージのビルドとプッシュ例

```bash
# ECR ログイン
aws ecr get-login-password --region ap-northeast-1 | \
  docker login --username AWS --password-stdin <AWS_ACCOUNT_ID>.dkr.ecr.ap-northeast-1.amazonaws.com

# ビルド＆タグ
docker build -t mobile-order-app .
docker tag mobile-order-app:latest <AWS_ACCOUNT_ID>.dkr.ecr.ap-northeast-1.amazonaws.com/mobile-order-app:latest

# プッシュ
docker push <AWS_ACCOUNT_ID>.dkr.ecr.ap-northeast-1.amazonaws.com/mobile-order-app:latest
```

※ ECS で nginx と app を分ける場合は、nginx 用のイメージも別途ビルドし、同一タスク定義で複数コンテナとして定義します。その場合、ALB は nginx のポート 80 に向けます。

---

## 共通で行いたい設定

### ドメインと HTTPS

- **Route 53** でホストゾーンを作成し、EB や ALB の DNS 名を CNAME または A レコード（Alias）で向ける
- **ACM（Certificate Manager）** で証明書を発行（ap-northeast-1 などデプロイ先リージョン）
- **EB**: 環境の **ロードバランサー** 設定で HTTPS リスナーを追加し、ACM 証明書を紐付け
- **ALB（ECS の場合）**: リスナーに HTTPS:443 を追加し、ACM 証明書を紐付け

### ストレージ（ログ・アップロード）

- 永続化したいファイルは **S3** に保存する構成にすると、複数インスタンスでも一貫します
- Laravel の `config/filesystems.php` に S3 ディスクを追加し、`FILESYSTEM_DISK=s3` や `SESSION_DRIVER=database` などにするとよいです
- ログは **CloudWatch Logs** に送る設定も検討してください

### キュー・スケジューラ

- `QUEUE_CONNECTION=sqs` にし、**SQS** キューを作成してワーカー（EB のワーカー環境や ECS タスク）で処理する
- スケジューラは **EB の cron** や **EventBridge (CloudWatch Events)** で `php artisan schedule:run` を定期実行

---

## リージョン

日本向けなら **ap-northeast-1（東京）** を推奨します。EB や ECS の作成時にリージョンを選択できます。

---

## 次のステップ

1. 上記のどれか一つの方式（まずは **Elastic Beanstalk + RDS**）で環境を 1 つ作成する
2. 環境変数を設定し、zip または `eb deploy` でデプロイ
3. RDS でマイグレーション実行後、`APP_URL` で管理画面や LIFF にアクセスして動作確認
4. ドメイン・HTTPS・S3/ログの切り替えを順次実施

不明な点があれば、利用する方式（EB / ECS / Lightsail）を教えてもらえれば、その方式に絞った具体的な手順も追記できます。
