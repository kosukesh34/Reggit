#!/bin/bash

echo "Mobile Order アプリのセットアップを開始します..."

# .envファイルの作成
if [ ! -f .env ]; then
    echo ".envファイルを作成しています..."
    cp env.example .env
    echo ".envファイルを作成しました。LINE_CHANNEL_ACCESS_TOKEN、LINE_CHANNEL_SECRET、LINE_LIFF_URLを設定してください。"
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

# データベースマイグレーション
echo "データベースマイグレーションを実行しています..."
sleep 5  # データベースの起動を待つ
docker-compose exec -T app php artisan migrate

# シーダーの実行
read -p "サンプル商品データを投入しますか？ (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    docker-compose exec -T app php artisan db:seed
fi

echo "セットアップが完了しました！"
echo "アプリケーションは http://localhost:8080 でアクセスできます。"


