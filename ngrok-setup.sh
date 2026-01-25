#!/bin/bash

echo "ngrokセットアップを開始します..."

# ngrokの認証トークンを取得
if [ -z "$NGROK_AUTH_TOKEN" ]; then
    echo ""
    echo "ngrokの認証トークンが必要です。"
    echo "1. https://dashboard.ngrok.com/get-started/your-authtoken にアクセス"
    echo "2. 認証トークンをコピー"
    echo "3. 以下のコマンドを実行してください："
    echo "   export NGROK_AUTH_TOKEN='your_token_here'"
    echo "   ./ngrok-setup.sh"
    echo ""
    exit 1
fi

# ngrok設定ファイルを更新
sed -i.bak "s/YOUR_NGROK_AUTH_TOKEN/$NGROK_AUTH_TOKEN/g" docker/ngrok/ngrok.yml

echo "ngrok設定ファイルを更新しました。"

# Dockerコンテナを再起動
echo "Dockerコンテナを再起動しています..."
docker-compose up -d ngrok

echo ""
echo "ngrokが起動しました！"
echo "以下のURLでアクセスできます："
echo ""
echo "ngrokダッシュボード: http://localhost:4040"
echo ""
echo "公開URLを確認するには："
echo "  docker-compose logs ngrok | grep 'started tunnel'"
echo ""


