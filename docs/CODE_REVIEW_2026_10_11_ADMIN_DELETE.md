# 管理会員削除のローカル原子性

会員のsoft deleteの後、LINE IDの保存やユーザー削除が失敗すると、会員だけが削除済みになる。次回findForWriteで取得できず通常の削除操作を完了できない。外部連携なしでもUser deletingイベントへの例外注入で再現する。

外部DELETEをロック外に維持し、その後のユーザー・会員・token・LINE ID更新をuser→memberロック順の一つのtransactionへまとめる。明示project条件と既存店舗scopeを維持し、外部応答待ちに所有者または顧客IDが変更された場合はローカル削除を拒否する。既存のtoken cleanup例外扱いとuser欠損時の動作は維持する。

検証: 修正前4 failed / 2 passed、20 assertions。修正後6 passed / 29 assertions（6.09秒）。保存例外によるrollback、外部失敗、顧客変更、所有者変更、外部削除後のlocal失敗と再試行、成功経路を確認。Pint対象2ファイル・全PHPStan（1 worker）成功、独立5軸レビューでブロッカーなし。

SmaregiApiService::deleteCustomerは既に404を成功として扱い、既存SmaregiApiTestも検証している。新しい再試行テストはそのadapter契約に従う。外部処理とDBを単一transactionにする保証はないが、local失敗後の再試行を可能にする。外部実接続・本番DB操作は未実施。最新HEADの全CIはマージゲートで新規実行する。
