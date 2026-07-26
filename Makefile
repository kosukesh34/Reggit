# Reggit - Docker 経由で artisan を実行するための Makefile
# 使い方: make up → make migrate → make route-list など

.PHONY: up down migrate seed reconcile-points test-env setup-dev route-list shell composer test test-db npm-build build-deploy deploy ngrok-sync smaregi-webhook-doctor smaregi-webhook-probe

# コンテナ起動（バックグラウンド）
up:
	docker-compose up -d

# コンテナ停止
down:
	docker-compose down

# マイグレーション実行
migrate:
	docker-compose exec app php artisan migrate

# 開発用サンプルデータ投入
seed:
	docker-compose exec app php artisan db:seed --force

# members.points と point_transactions の整合
reconcile-points:
	docker-compose exec app php artisan members:reconcile-points

# テスト用 .env.testing（無ければ example から作成し APP_KEY を .env から同期）
test-env:
	@if [ ! -f .env.testing ]; then \
		cp env.testing.example .env.testing; \
		echo ".env.testing を作成しました"; \
	fi
	@if [ -f .env ] && grep -q '^APP_KEY=base64:' .env; then \
		KEY=$$(grep '^APP_KEY=' .env | head -1); \
		if ! grep -q '^APP_KEY=base64:' .env.testing 2>/dev/null; then \
			sed -i.bak "s|^APP_KEY=.*|$$KEY|" .env.testing && rm -f .env.testing.bak; \
		fi; \
	fi

# 初回セットアップ（migrate + seed + ポイント整合）
setup-dev: up migrate test-env test-db seed reconcile-points

# ルート一覧（admin のみ）
route-list:
	docker-compose exec app php artisan route:list --path=admin

# app コンテナのシェル（php artisan を直接叩きたいとき）
shell:
	docker-compose exec app sh

# Composer（例: composer install）
composer-install:
	docker-compose exec app composer install

# キャッシュクリア
cache-clear:
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear

# フロント資産ビルド（Vite + React）
npm-build:
	npm ci
	npm run build

# テスト用DB作成（Docker 内でテストする前に1回実行）
# reggit_user に reggit_test への権限を付与し、開発DB (reggit) を破壊しないようにする
test-db:
	docker-compose exec db mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS reggit_test; GRANT ALL PRIVILEGES ON \`reggit_test\`.* TO 'reggit_user'@'%'; FLUSH PRIVILEGES;"

# テスト実行（reggit_test のみ使用。開発 DB reggit は触らない）
test: test-env test-db
	docker-compose exec -e DB_DATABASE=reggit_test app php artisan test

# 依存関係の脆弱性チェック（セキュリティ）
audit:
	docker-compose exec app composer audit

# セキュリティテストのみ実行
test-security: test-env test-db
	docker-compose exec -e DB_DATABASE=reggit_test app php artisan test tests/Feature/SecurityTest.php

# AWS Elastic Beanstalk 用デプロイ zip をビルド（Docker プラットフォーム用）
# 出力: deploy-docker.zip → EB の「アップロードとデプロイ」でアップロード
build-deploy:
	./scripts/build-deploy-zip.sh

# Xserver 本番へデプロイ（reggit deploy と同じ）
deploy:
	./scripts/reggit deploy

# ngrok の公開 URL を APP_URL に同期（要: ngrok 起動済み・localhost:4040）
ngrok-sync:
	docker-compose exec app php artisan smaregi:sync-ngrok-url

# スマレジ Webhook 設定の診断
smaregi-webhook-doctor:
	docker-compose exec app php artisan smaregi:webhook-doctor

# スマレジ API 疎通診断（トークン・店舗・会員ポイント）
smaregi-api-doctor:
	docker-compose exec app php artisan smaregi:api-doctor

# ローカル・公開 URL へテスト POST
smaregi-webhook-probe:
	docker-compose exec app php artisan smaregi:webhook-doctor --probe
