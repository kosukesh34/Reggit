#!/bin/bash

echo "=========================================="
echo "ngrok 公開URL取得"
echo "=========================================="
echo ""

# ngrokが起動しているか確認
if ! pgrep -x "ngrok" > /dev/null; then
    echo "ngrokが起動していません。"
    echo "まず、ngrokを起動してください："
    echo "  ./start-ngrok.sh"
    exit 1
fi

# ngrok APIからURLを取得
NGROK_URL=$(curl -s http://localhost:4040/api/tunnels 2>/dev/null | grep -o 'https://[a-z0-9-]*\.ngrok-free\.app\|https://[a-z0-9-]*\.ngrok-free\.dev' | head -1)

if [ -z "$NGROK_URL" ]; then
    echo "公開URLを取得できませんでした。"
    echo "ngrokダッシュボードで確認してください: http://localhost:4040"
    exit 1
fi

echo "公開URL:"
echo "  $NGROK_URL"
echo ""
echo "=========================================="
echo "エンドポイントURL一覧"
echo "=========================================="
echo ""
echo "LIFFアプリ:"
echo "  ${NGROK_URL}/liff"
echo ""
echo "Webhook:"
echo "  ${NGROK_URL}/api/line/webhook"
echo ""
echo "フロントエンド:"
echo "  ${NGROK_URL}/"
echo ""
echo "管理画面:"
echo "  ${NGROK_URL}/admin"
echo ""
echo "=========================================="
echo "LINE Developers Consoleでの設定"
echo "=========================================="
echo ""
echo "1. LIFFアプリのエンドポイントURL:"
echo "   ${NGROK_URL}/liff"
echo ""
echo "2. Webhook URL:"
echo "   ${NGROK_URL}/api/line/webhook"
echo ""

