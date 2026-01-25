#!/bin/bash

echo "ngrokを停止しています..."

if [ -f /tmp/ngrok.pid ]; then
    PID=$(cat /tmp/ngrok.pid)
    if ps -p $PID > /dev/null 2>&1; then
        kill $PID
        echo "ngrokを停止しました（PID: $PID）"
    else
        echo "ngrokプロセスが見つかりませんでした。"
    fi
    rm /tmp/ngrok.pid
else
    # PIDファイルがない場合、プロセス名で検索
    if pgrep -x "ngrok" > /dev/null; then
        pkill ngrok
        echo "ngrokを停止しました。"
    else
        echo "ngrokは起動していません。"
    fi
fi


