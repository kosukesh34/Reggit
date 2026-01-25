#!/bin/bash

echo "=========================================="
echo "LINE LIFF アプリ セットアップ"
echo "=========================================="
echo ""

# ngrokが起動しているか確認
if ! docker-compose ps | grep -q "mobile_order_ngrok.*Up"; then
    echo "ngrokが起動していません。"
    echo "まず、ngrokを起動してください："
    echo ""
    echo "  1. ngrok認証トークンを設定:"
    echo "     ./start-ngrok.sh"
    echo ""
    echo "  2. ngrokを起動:"
    echo "     docker-compose up -d ngrok"
    echo ""
    exit 1
fi

echo "ngrokが起動しています。"
echo ""

# ngrokの公開URLを取得
echo "ngrokの公開URLを取得中..."
sleep 2

NGROK_URL=$(docker-compose logs ngrok 2>/dev/null | grep -o 'https://[a-z0-9-]*\.ngrok-free\.app' | head -1)

if [ -z "$NGROK_URL" ]; then
    echo "ngrokの公開URLを取得できませんでした。"
    echo "ngrokダッシュボードで確認してください: http://localhost:4040"
    echo ""
    read -p "ngrokの公開URLを手動で入力してください: " NGROK_URL
fi

if [ -z "$NGROK_URL" ]; then
    echo "URLが入力されませんでした。"
    exit 1
fi

LIFF_URL="${NGROK_URL}/liff"

echo ""
echo "=========================================="
echo "LIFFアプリのエンドポイントURL"
echo "=========================================="
echo ""
echo "エンドポイントURL:"
echo "  ${LIFF_URL}"
echo ""
echo "=========================================="
echo "LINE Developers Consoleでの設定"
echo "=========================================="
echo ""
echo "1. https://developers.line.biz/console/ にアクセス"
echo "2. プロバイダーとチャネルを選択"
echo "3. 「LIFF」タブを開く"
echo "4. LIFFアプリを選択（または新規作成）"
echo "5. エンドポイントURLに以下を設定:"
echo ""
echo "   ${LIFF_URL}"
echo ""
echo "6. サイズ: Full"
echo "7. スコープ: profile openid email"
echo "8. ボットリンク機能: オン"
echo "9. 保存"
echo ""
echo "=========================================="
echo "Webhook URLの設定"
echo "=========================================="
echo ""
echo "Messaging API設定で、Webhook URLを以下に設定:"
echo ""
echo "   ${NGROK_URL}/api/line/webhook"
echo ""
echo "=========================================="
echo "完了"
echo "=========================================="
echo ""
echo "設定が完了したら、LINEアプリでミニアプリを開いてください。"
echo ""

