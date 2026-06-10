#!/usr/bin/env bash
# macOS launchd に Takt watch を常駐サービスとして登録
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
LABEL="com.reggit.takt-watch"
PLIST="$HOME/Library/LaunchAgents/${LABEL}.plist"
SCRIPT="$ROOT/scripts/takt-watch.sh"

chmod +x "$SCRIPT"
mkdir -p "$ROOT/.takt/daemon"

cat > "$PLIST" <<EOF
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
  <key>Label</key>
  <string>${LABEL}</string>
  <key>ProgramArguments</key>
  <array>
    <string>${SCRIPT}</string>
  </array>
  <key>WorkingDirectory</key>
  <string>${ROOT}</string>
  <key>RunAtLoad</key>
  <true/>
  <key>KeepAlive</key>
  <true/>
  <key>StandardOutPath</key>
  <string>${ROOT}/.takt/daemon/launchd-stdout.log</string>
  <key>StandardErrorPath</key>
  <string>${ROOT}/.takt/daemon/launchd-stderr.log</string>
  <key>EnvironmentVariables</key>
  <dict>
    <key>PATH</key>
    <string>${HOME}/.local/bin:/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin</string>
    <key>HOME</key>
    <string>${HOME}</string>
  </dict>
</dict>
</plist>
EOF

# 手動起動中の watch を停止
pkill -f "takt watch" 2>/dev/null || true
sleep 1

launchctl bootout "gui/$(id -u)/${LABEL}" 2>/dev/null || true
launchctl bootstrap "gui/$(id -u)" "$PLIST"
launchctl enable "gui/$(id -u)/${LABEL}"
launchctl kickstart -k "gui/$(id -u)/${LABEL}"

# オーケストレーター（Issue 巡回）も別プロセスで登録
"$ROOT/scripts/install-takt-orchestrator-daemon.sh"

echo "Installed: $PLIST"
echo "Logs: $ROOT/.takt/daemon/"
echo "Status: launchctl print gui/$(id -u)/${LABEL}"
