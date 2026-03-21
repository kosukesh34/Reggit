# Reggit - Docker 経由で artisan を実行するための Makefile
# 使い方: make up → make migrate → make route-list など

.PHONY: up down migrate route-list shell composer test test-db build-deploy

# コンテナ起動（バックグラウンド）
up:
	docker-compose up -d

# コンテナ停止
down:
	docker-compose down

# マイグレーション実行
migrate:
	docker-compose exec app php artisan migrate

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

# テスト用DB作成（Docker 内でテストする前に1回実行）
test-db:
	docker-compose exec db mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS reggit_test;"

# テスト実行（要: make up & make test-db）
test: test-db
	docker-compose exec app php artisan test

# 依存関係の脆弱性チェック（セキュリティ）
audit:
	docker-compose exec app composer audit

# セキュリティテストのみ実行
test-security: test-db
	docker-compose exec app php artisan test tests/Feature/SecurityTest.php

# AWS Elastic Beanstalk 用デプロイ zip をビルド（Docker プラットフォーム用）
# 出力: deploy-docker.zip → EB の「アップロードとデプロイ」でアップロード
build-deploy:
	./scripts/build-deploy-zip.sh
