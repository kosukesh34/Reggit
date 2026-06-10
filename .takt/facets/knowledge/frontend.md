# Reggit フロントエンド知識

## スタック

- Blade テンプレート（`resources/views/`）
- 管理画面: `resources/views/admin/`, CSS `public/css/admin/`
- LIFF / フロント: `resources/views/frontend/`, CSS `public/css/frontend/`
- ネイティブシェル: `resources/views/frontend/native.blade.php`

## ローカル確認

- `docker compose up -d` → `http://localhost:8080`
- LIFF 系は `project_key` 付き URL が必要な場合あり

## UI 変更時

- スクリーンショットは `.takt/evidence/screenshots/` に保存し PR に含める
- マルチテナント: 表示データは `project_id` スコープを維持

## テスト

- Feature テスト: `docker compose exec app php artisan test`
