#!/bin/bash

echo "=========================================="
echo "ngrok 起動スクリプト"
echo "=========================================="
echo ""

# 既存のngrokプロセスを確認
if pgrep -x "ngrok" > /dev/null; then
    echo "ngrokは既に起動しています。"
    echo "既存のプロセスを停止しますか？ (y/n)"
    read -r answer
    if [ "$answer" = "y" ]; then
        pkill ngrok
        sleep 2
    else
        echo "既存のngrokプロセスをそのまま使用します。"
        exit 0
    fi
fi

# ngrok認証トークンの確認
if [ -z "$NGROK_AUTH_TOKEN" ]; then
    echo "ngrokの認証トークンが必要です。"
    echo ""
    echo "1. https://dashboard.ngrok.com/get-started/your-authtoken にアクセス"
    echo "2. 認証トークンをコピー"
    echo "3. 以下のいずれかの方法で設定してください："
    echo ""
    echo "   方法1: 環境変数で設定"
    echo "   export NGROK_AUTH_TOKEN='your_token_here'"
    echo "   ./start-ngrok.sh"
    echo ""
    echo "   方法2: このスクリプトで入力"
    echo ""
    read -p "認証トークンを入力してください（Enterでスキップ）: " token
    
    if [ ! -z "$token" ]; then
        export NGROK_AUTH_TOKEN="$token"
    else
        echo "認証トークンが設定されていません。ngrokを起動できません。"
        exit 1
    fi
fi

# ngrokをバックグラウンドで起動
echo "ngrokを起動しています..."
ngrok http 8080 --log=stdout > /tmp/ngrok.log 2>&1 &
NGROK_PID=$!

# プロセスIDを保存
echo $NGROK_PID > /tmp/ngrok.pid

echo "ngrokを起動しました（PID: $NGROK_PID）"
echo ""

# 少し待ってからURLを取得
sleep 3

echo "=========================================="
echo "公開URLの確認"
echo "=========================================="
echo ""

# ngrok APIからURLを取得
NGROK_URL=$(curl -s http://localhost:4040/api/tunnels | grep -o 'https://[a-z0-9-]*\.ngrok-free\.app\|https://[a-z0-9-]*\.ngrok-free\.dev' | head -1)

if [ -z "$NGROK_URL" ]; then
    echo "公開URLを取得できませんでした。"
    echo "ngrokダッシュボードで確認してください: http://localhost:4040"
    echo ""
    echo "手動で確認する場合:"
    echo "  curl -s http://localhost:4040/api/tunnels | grep -o 'https://[a-z0-9-]*\.ngrok-free\.app'"
else
    echo "公開URL:"
    echo "  $NGROK_URL"
    echo ""
    echo "LIFFアプリのエンドポイントURL:"
    echo "  ${NGROK_URL}/liff"
    echo ""
    echo "Webhook URL:"
    echo "  ${NGROK_URL}/api/line/webhook"
    echo ""
fi

echo "=========================================="
echo "ngrokダッシュボード"
echo "=========================================="
echo "  http://localhost:4040"
echo ""
echo "ngrokを停止する場合:"
echo "  ./stop-ngrok.sh"
echo "  または"
echo "  pkill ngrok"
echo ""
