#!/usr/bin/env bash
# Elastic Beanstalk（Docker プラットフォーム）用デプロイ zip をビルドする。
# 使い方: ./scripts/build-deploy-zip.sh  または  make build-deploy
# 出力: プロジェクトルートに deploy-docker.zip が作成される。

set -e

# プロジェクトルートに移動（スクリプトが scripts/ にある前提）
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

OUTPUT_ZIP="${1:-deploy-docker.zip}"

echo "==> デプロイ用 zip をビルドします（出力: $OUTPUT_ZIP）"

# 既存の Dockerfile を退避
if [ -f Dockerfile ]; then
  cp Dockerfile Dockerfile.bak
  RESTORE_DOCKERFILE=1
else
  RESTORE_DOCKERFILE=0
fi

# EB 用 Dockerfile を Dockerfile としてコピー（zip に含めるため）
cp Dockerfile.eb Dockerfile

# 既存の zip を削除
rm -f "$OUTPUT_ZIP"

# デプロイ用 zip を作成（.env / vendor / 開発用ファイルは含めない）
zip -r "$OUTPUT_ZIP" . \
  -x "*.git*" \
  -x "*.env" \
  -x ".env.*" \
  -x "node_modules/*" \
  -x "vendor/*" \
  -x "storage/logs/*" \
  -x "storage/framework/cache/*" \
  -x "storage/framework/sessions/*" \
  -x "storage/framework/views/*" \
  -x "tests/*" \
  -x ".phpunit.result.cache" \
  -x "docs/*" \
  -x "*.md" \
  -x "mobile/*" \
  -x "*.zip" \
  -x "Dockerfile.bak"

# macOS で -x が効かないことがあるため、zip 内から明示的に削除
# 01_deploy.config のみ削除（Docker プラットフォームでは不要）。02_healthcheck.config は ELB パス用に残す
zip -d "$OUTPUT_ZIP" "docker-compose.yml" 2>/dev/null || true
zip -d "$OUTPUT_ZIP" ".ebextensions/01_deploy.config" 2>/dev/null || true

# Dockerfile を元に戻す
if [ "$RESTORE_DOCKERFILE" -eq 1 ]; then
  mv Dockerfile.bak Dockerfile
else
  rm -f Dockerfile
fi

echo "==> 完了: $OUTPUT_ZIP ($(du -h "$OUTPUT_ZIP" | cut -f1))"
echo "    AWS コンソールの Elastic Beanstalk → アップロードとデプロイ からこのファイルをアップロードしてください。"
