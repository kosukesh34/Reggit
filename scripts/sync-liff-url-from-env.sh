#!/usr/bin/env bash
# .env の APP_URL をモバイルの共通開発接続先に同期する（本番設定は変更しない）。
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

python3 - "$ROOT_DIR" <<'PY'
import json
from pathlib import Path
import re
import sys
from urllib.parse import urlparse

root = Path(sys.argv[1])
env = root / '.env'
config = root / 'mobile/reggit-skip/Sources/Reggit/Domain/Models/MobileConnectionEnvironment.swift'
if not env.is_file() or not config.is_file():
    sys.exit('Error: .env or mobile connection configuration not found')
url = next((line.split('=', 1)[1].strip().strip('"\'') for line in env.read_text().splitlines() if line.startswith('APP_URL=')), '')
parsed = urlparse(url)
if parsed.scheme not in ('http', 'https') or not parsed.netloc:
    sys.exit('Error: APP_URL must be an HTTP(S) URL')
source = config.read_text()
pattern = r'(case \.development: return )"[^"\n]*"'
updated, count = re.subn(pattern, lambda match: match[1] + json.dumps(url.rstrip('/')), source, count=1)
if count != 1:
    sys.exit('Error: development baseURL setting not found')
config.write_text(updated)
print('Synced development baseURL from .env; production configuration is unchanged')
PY
