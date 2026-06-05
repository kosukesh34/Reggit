#!/bin/bash
set -euo pipefail

SEED="${SEED:-1}"

echo "Reggit のセットアップを開始します..."

# .envファイルの作成
if [ ! -f .env ]; then
    echo ".envファイルを作成しています..."
    cp env.example .env
    echo ".envファイルを作成しました。LINE_CHANNEL_ACCESS_TOKEN、LINE_CHANNEL_SECRET、LINE_LIFF_URLを設定してください。"
fi

# テスト用 .env（開発 DB をテストで破壊しない）
if [ ! -f .env.testing ] && [ -f env.testing.example ]; then
    cp env.testing.example .env.testing
    echo ".env.testing を作成しました（テストは reggit_test を使用）。"
fi

# Dockerコンテナの起動
echo "Dockerコンテナを起動しています..."
docker-compose up -d

# Composerパッケージのインストール
echo "Composerパッケージをインストールしています..."
docker-compose exec -T app composer install

# アプリケーションキーの生成
echo "アプリケーションキーを生成しています..."
docker-compose exec -T app php artisan key:generate

# テスト用 DB
echo "テスト用データベースを準備しています..."
docker-compose exec -T db mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS reggit_test; GRANT ALL PRIVILEGES ON \`reggit_test\`.* TO 'reggit_user'@'%'; FLUSH PRIVILEGES;" 2>/dev/null || true

# データベースマイグレーション
echo "データベースマイグレーションを実行しています..."
sleep 5
docker-compose exec -T app php artisan migrate --force

# シーダーの実行
if [ "$SEED" = "1" ]; then
    echo "サンプルデータを投入しています..."
    docker-compose exec -T app php artisan db:seed --force
    docker-compose exec -T app php artisan members:reconcile-points
else
    read -p "サンプルデータを投入しますか？ (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        docker-compose exec -T app php artisan db:seed --force
        docker-compose exec -T app php artisan members:reconcile-points
    fi
fi

echo "セットアップが完了しました！"
echo "アプリケーションは http://localhost:8080 でアクセスできます。"
echo "管理画面ログイン例: demo_store / DemoStore#2026"
