#!/usr/bin/env bash
# 日本郵便APIの疎通確認スクリプト
# 使い方: ./scripts/test-japanpost-api.sh
# .env の JAPANPOST_* を読み、トークン取得 → 郵便番号検索 を curl で実行します。

set -e
cd "$(dirname "$0")/.."
ENV_FILE="${ENV_FILE:-.env}"

if [ ! -f "$ENV_FILE" ]; then
  echo "Error: $ENV_FILE not found"
  exit 1
fi

get_env() {
  grep -E "^${1}=" "$ENV_FILE" | sed 's/^[^=]*=//' | sed 's/^["'\'']//;s/["'\'']$//' | tr -d '\r'
}

HOST=$(get_env "JAPANPOST_API_HOST")
CID=$(get_env "JAPANPOST_CLIENT_ID")
SECRET=$(get_env "JAPANPOST_CLIENT_SECRET")

echo "=== このマシンから日本郵便APIへつなぐときの送信元IP（要・日本郵便側に登録） ==="
OUTBOUND_IP=$(curl -s --max-time 5 https://api.ipify.org 2>/dev/null || curl -s --max-time 5 https://ifconfig.me/ip 2>/dev/null || echo "取得できませんでした")
echo "送信元（外向き）IP: ${OUTBOUND_IP}"
echo "※ 日本郵便の「固定IPアドレス」にこのIPが登録されていないと 403 になることがあります。"
echo ""

echo "=== .env 確認 ==="
echo "JAPANPOST_API_HOST=${HOST:-(未設定)}"
echo "JAPANPOST_CLIENT_ID=${CID:-(未設定)}"
echo "JAPANPOST_CLIENT_SECRET=${SECRET:+（設定済・表示省略）}"
echo ""

if [ -z "$HOST" ] || [ -z "$CID" ] || [ -z "$SECRET" ]; then
  echo "Error: JAPANPOST_* のいずれかが未設定です。.env を確認してください。"
  exit 1
fi

echo "=== 1. トークン取得 (POST https://${HOST}/api/v1/j/token) ==="
TOKEN_RESP=$(curl -s -w "\n%{http_code}" -X POST "https://${HOST}/api/v1/j/token" \
  -H "Content-Type: application/json" \
  -H "x-forwarded-for: 127.0.0.1" \
  -d "{\"grant_type\":\"client_credentials\",\"client_id\":\"${CID}\",\"secret_key\":\"${SECRET}\"}")
HTTP_CODE=$(echo "$TOKEN_RESP" | tail -n1)
BODY=$(echo "$TOKEN_RESP" | sed '$d')
echo "HTTP $HTTP_CODE"
echo "$BODY" | head -c 500
echo ""
echo ""

if [ "$HTTP_CODE" != "200" ]; then
  echo "トークン取得に失敗しました。"
  echo "- 403 の場合: 登録情報の不一致。本番用クライアントID/シークレット・IP許可・契約状態を日本郵便側で確認してください。"
  echo "- スタブ利用時は JAPANPOST_API_HOST=stub-qz73x.da.pf.japanpost.jp とスタブ用の ID/シークレットにしてください。"
  exit 1
fi

TOKEN=$(echo "$BODY" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
if [ -z "$TOKEN" ]; then
  echo "Error: レスポンスから token を取得できませんでした"
  exit 1
fi

echo "=== 2. 郵便番号検索 (GET https://${HOST}/api/v1/searchcode/8111344) ==="
SEARCH_RESP=$(curl -s -w "\n%{http_code}" -X GET "https://${HOST}/api/v1/searchcode/8111344?page=1&limit=5" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "x-forwarded-for: 127.0.0.1")
SEARCH_CODE=$(echo "$SEARCH_RESP" | tail -n1)
SEARCH_BODY=$(echo "$SEARCH_RESP" | sed '$d')
echo "HTTP $SEARCH_CODE"
echo "$SEARCH_BODY" | head -c 800
echo ""
echo ""

if [ "$SEARCH_CODE" = "200" ] && echo "$SEARCH_BODY" | grep -q '"addresses"'; then
  echo "OK: 住所の取得まで成功しました。"
else
  echo "検索でエラーまたは住所が取得できませんでした。上記の HTTP コードと本文を確認してください。"
  exit 1
fi
