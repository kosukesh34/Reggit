# 会員引き継ぎ: 現在の認証状態とコード発行の整合性（2026-10-11）

## 確認した問題

1. 認証リゾルバーが確認済みの User／Device を Claim が入力モデルのまま使う。別の引き継ぎで User が削除され Device が移動しても、古い入力から Device を再移動し、別コードを消費できた。
2. Issue の旧コード消費と新コード作成が別の保存だった。新作成失敗で旧コードが失効し、別接続の発行が重なると未消費コードが2件残った。
3. 本人のコード発行で、入力取得後に認証世代が変わっても発行を許可した。

## 変更と境界

Claim はコードの出所を読み、関連 User を ID 順で先にロックする。現在 actor の存在・認証世代、ロックしたコードの user／hash／project／未消費／期限、現在の source 会員と member ID、actor の会員状態、現在 Device の存在・owner・token を確認する。fresh models で identity 移動とコード消費を同じ TX に保存する。

Issue は現在 User を先にロックし、現在の会員を確認する。本人発行では入力の認証世代と照合する。管理発行の対象 User は認証 actor ではないため、その世代変更で管理権限を失わせず現在会員を使う。admin ID は従来どおり認可済み管理呼び出し元が渡す。旧コード消費と新コード作成は同じ TX で確定する。

両経路で User を先に取得し、共通の User を持つ発行／使用を直列化する。Claim の入力モデルは retry 中も更新しない。既存 DeviceService などとの DB deadlock は Laravel の TX 3回 retry に委ねる。新しい汎用 lock／outbox module や API 形式は追加していない。

## 検証

- 変更前2テスト失敗（7 assertions / 5.66秒）。実 AuthenticatedDeviceResolver の token確認後に正規 Claim を実行し、古い入力でさらに Claim すると Device が別 source に移る。MemberTransferCode creating の例外注入で旧コード消費が残る。
- 関連4クラス16テスト成功（94 assertions / 20.33秒）。既存 HTTP／管理発行／端末登録、認証世代・device owner／token／削除・会員新規作成後の拒否、本人と管理発行の互換性を確認した。
- DatabaseTruncation の2接続 fixture: primary の作成中に writer で同じ Issue を呼ぶ。writer は1205で待機失敗し、primary commit後の発行は成功し、有効コードは1件だけ残る。
- Claim を一時コピーで変更前へ戻すと6テスト失敗（14 assertions / 6.12秒）。
- Issue を変更前へ戻すと3テスト失敗・管理発行1テスト成功（9 assertions / 11.24秒）。重なった発行では実際に有効コードが2件になった。
- Pint（4ファイル）・全体 PHPStan 成功。独立5軸レビューで blocker なし。diff check 成功。関連テスト後の最後の production変更は Issue の fresh owner の変数名整理のみ。
- コミット後の全ローカル CI は scripts/merge-pr.sh で実施し、対象 HEAD と結果を PR に記録する。

## 検証範囲

認証後の状態変化は同一プロセス内の制御された処理順、発行競合は別 DB 接続の重なりを検証した。実並行 HTTP の負荷、既存 DeviceService とのすべての競合組み合わせ、ブラウザー E2E は追加実行していない。token変更は DB の状態変更、保存失敗は明示的な例外注入である。外部 API や本番 DB は使用していない。
