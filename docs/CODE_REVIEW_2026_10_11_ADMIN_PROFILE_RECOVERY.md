# 管理プロフィールの外部送信回復

## 指摘と修正

管理画面はローカル保存後に直接PATCHしていた。失敗は再送されず、次回の会員取得で古いプロフィールを取り込む。外部例外は保存済みの編集まで失敗として返し、外側transactionではrollback前に送信する。

会員画面と同じプロフィール配送moduleを利用し、ユーザー・会員の保存transaction内へ送信要求を記録する。送信は最終commit後、失敗は既存scannerが再送し、pending中のinboundはローカル変更を保護する。実際に変更した項目のみを送信し、会員番号はcustomerCodeへ変換する。管理画面の重複PATCH組立・配送処理を削除する。

## 検証

- 旧ソースで古いinboundによる変更消失・外部例外・再送欠落を再現。最初のbaseline4件のうちouter rollbackはmock不足だったため、その結果を送信回帰の証拠として流用しない。
- 補強後のouter rollback baselineは実際の送信数1対期待0で失敗（2 assertions）。
- 関連37件/148 assertions成功（116.82秒）。補強後のouter rollback単独も1件/5 assertions成功（6.29秒）。
- Pint対象3ファイルと全PHPStan（1 worker）成功。独立5軸レビューでブロッカーなし。
- 全CIはマージゲートで最新HEADへ新規実行する。

外部実接続は未実施。任意項目の空欄の外部クリアとcustom field部分更新の別問題は本PRの対象に含めない。
