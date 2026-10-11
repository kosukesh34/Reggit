# 来店migrationのrollback

MySQLがproject_id外部キーの単独索引を(project_id, smaregi_transaction_id)のunique索引へ置き換える場合、downでuniqueを削除するとSQL1553で停止する。unique削除前にproject_id単独索引を必要時に復元する。

隔離MySQL8.4で、修正前は1件失敗（2 assertions、SQL1553）、修正後は1件成功（9 assertions、3.43秒）。単独索引なしからのrollback、外部キー保持、列・unique削除、再適用と再rollbackを確認。Pint対象2ファイル・全PHPStan（1 worker）成功。独立レビューでブロッカーなし。

本番DBでのmigration操作は未実施。変更はrollback経路のみ。最新PR HEADの全CIは指定マージゲートで新規実行する。
