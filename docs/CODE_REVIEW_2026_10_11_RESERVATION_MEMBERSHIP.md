# 予約の現在会員資格レビュー

## 必須指摘

ReservationStoreUseCase / ReservationUpdateUseCaseはトランザクション前のUser.memberを会員資格に使っていた。読み込み済みMemberが削除・別Userへ移管された場合でも予約を作成・変更できた。通常APIとV1 APIは同じUseCaseを呼ぶため両経路に影響する。修正前に作成・変更×削除・移管の4ケースが失敗した。

## 修正と設計

既存トランザクションで現在projectのUserをロックし、存在・入力認証世代・現在Memberを確認する。User → Member → Storeの順で保存まで保持し、現在UserをReservationServiceへ渡す。会員資格がなければ既存requiresMembership出力に戻す。容量のStoreロック、予約採番のProjectロック、3回のTX retryを維持する。管理画面などの直接ReservationServiceによる非会員予約は変更しない。新しいmodule/interfaceは導入せず、予約書き込みの既存interface内で資格確認と保存をまとめる。

## 検証と限界

- 修正前の資格4ケース: 4 failed / 4 assertions。
- 作成・変更×Member削除／移管／User削除／認証世代変更の8ケースと関連通常API・V1・容量・複数店舗採番: 24 passed / 75 assertions。
- 一時コピーで修正を外すと8ケースが失敗する。
- Pint変更3ファイル・全体PHPStan成功、最終5軸静的レビューにブロッカーなし。
- 容量テストは独立プロセス・DB接続で実行する既存テスト。会員移管との同時実行を実測したものではない。取得済み会員状態の制御した変更による回帰確認を行った。

本番・外部API・実端末は未確認。コミット済み最新HEADに指定の全ローカルマージゲートを別途実行する。
