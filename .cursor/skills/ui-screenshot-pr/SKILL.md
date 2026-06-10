# UI screenshot PR

> UI 変更を含む MR に、変更後（できれば変更前後）のスクリーンショットを貼る。

## Trigger

- Blade / CSS / LIFF / 管理画面の実装完了時
- Takt `reggit-github` の implement ステップ（UI 変更あり）

## Process

1. `docker compose up -d` → `http://localhost:8080`
2. 対象 URL を開く（管理画面ログインが必要ならテスト用アカウント or 該当画面の最小再現）
3. `.takt/evidence/screenshots/{task-slug}/` に PNG 保存
4. git commit に含める
5. MR 本文に相対パスで Markdown 画像を記載

## MR テンプレート

```markdown
## UI スクリーンショット

| 画面 | キャプチャ |
|------|-----------|
| 変更後 | ![after](.takt/evidence/screenshots/TASK/after-desktop.png) |
```

## References

- `.takt/facets/instructions/implement.md`
