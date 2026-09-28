# 分析・配信 MCP サーバー

`scripts/analysis-mcp.mjs` は標準入出力で動く MCP サーバーです。Node.js 20 以降が必要です。

MCP クライアントのサーバー設定例:

```json
{
  "mcpServers": {
    "reggit-analysis": {
      "command": "node",
      "args": ["/absolute/path/to/Reggit/scripts/analysis-mcp.mjs"],
      "env": {
        "REGGIT_BASE_URL": "http://localhost:8080",
        "REGGIT_PROJECT_KEY": "sb_skx928r2",
        "REGGIT_API_TOKEN": "店舗のAPIトークン"
      }
    }
  }
}
```

管理画面の「分析から配信」で「外部AIによる分析APIの利用を許可」を有効にし、分析専用トークンを発行してください。初期状態は分析・配信とも無効です。配信も使う場合だけ「外部AIからの全会員配信を許可」を有効にします。画面のURLにあるプロジェクトキーと、発行した分析専用トークンを指定します。トークンは設定ファイルを共有・コミットしないでください。90日で期限切れになり、画面から失効・再発行できます。実際の起動方法はMCPクライアントごとに異なります。

公開ツールは `get_analysis_summary`（期間集計）、`list_delivery_coupons`（配信可能なクーポン）、`deliver_coupon_to_all`（全会員への即時配信）です。AIクライアントは集計を解釈して提案できます。配信ツールは実際の配布を行うため、利用者の明示的な承認を得てから呼び出してください。サーバー側の配信設定が無効なら実行できません。MCPサーバー自体に定期実行や自動判断のスケジューラーはありません。
