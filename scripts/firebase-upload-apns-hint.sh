#!/usr/bin/env bash
# Firebase Console に APNs .p8 を登録する手順メモ（公式 API が無いため手動）。
# 使い方: ./scripts/firebase-upload-apns-hint.sh
set -euo pipefail
cat <<'EOF'
Firebase Console で APNs Auth Key を登録してください。

1. https://console.firebase.google.com/project/reggit-dbb5a/settings/cloudmessaging
2. Apple アプリ「Reggit Skip」(com.reggit.default) の APNs authentication key で Upload
3. ファイル: ~/Downloads/AuthKey_XQ953UR7Z4.p8
4. Key ID: XQ953UR7Z4
5. Team ID: T29T2L39T5

サービスアカウント JSON は storage/app/firebase/reggit-dbb5a-adminsdk.json に配置済み（gitignore）。
EOF
