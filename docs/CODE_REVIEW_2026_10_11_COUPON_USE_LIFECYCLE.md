# クーポン利用時の会員状態・所有者

Web・API共通のCouponUseMemberCouponUseCaseはcached User.memberを利用し、利用クーポンだけをロックしていました。会員削除や所有者変更が取得後にcommitしても、使用状態・利用履歴・使用回数を更新できました。

利用transactionの最初に、default scopeを保持したMemberのID・project・userを照合してロックします。一致しなければ既存member登録必要ValidationExceptionを返します。member→issued couponの順は付与・抽選経路と整合し、既存の二重使用・利用期間判定は維持します。

回帰はcached relationを明示保持し、拒否とクーポン未使用・used_at未設定・redemption未作成・used_count保持を確認。修正前2失敗・6成功（26 assertions、6.07秒）、修正後8成功（34 assertions、7.24秒）。外部API実接続なし。最新HEADで指定マージゲートの全ローカル検証を新規実行し、成功時のみマージします。GitHub Actions結果は使用しません。

設計判断: 利用moduleのinterfaceで現在の会員状態と所有者を確定する。正しさを両コントローラーへ分散せず、共通module内にlocalityを保持します。新しい汎用層は追加しません。

所有者照合を外した隔離mutationは1失敗・7成功（30 assertions、9.30秒）。Pint2ファイル・全体PHPStan成功、独立5軸レビューでブロッカーなし。
