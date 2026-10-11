# 端末限定クーポンの現在会員確認

端末限定クーポンは共通特典処理を通らず、cached User.memberを用いて発行します。会員取得後に削除・所有者変更がcommitしても、端末獲得記録とクーポンを作成できました。

発行transaction内の最初に、default scopeを保ったMemberのID・project・userを照合してロックします。現在の会員が一致しなければ既存の会員登録必要ValidationExceptionを返し、HTTP 422契約を維持します。member→device issue→couponの順で更新し、端末一度限りのDB一意制約は維持します。

検証: 修正前2失敗・2成功（9 assertions、5.67秒）、修正後4成功（13 assertions、8.00秒）。cached relationを保持した削除・所有者変更後の拒否と、両テーブル未作成を確認。外部API実接続なし。最新コミットへ指定merge-pr.shの全ローカル検証を新規実行し、成功時のみマージします。GitHub Actionsは使用しません。

設計判断: 発行moduleのinterfaceで会員状態と所有者の現在値を確定する。呼び出し元での事前取得を発行許可として使わず、既存module内に正しさのlocalityを保つ。汎用の新しい抽象層は不要です。

所有者チェックを隔離コピーで外すmutationは1失敗・3成功（11 assertions、7.91秒）となり回帰検出を確認。変更2ファイルPint・全体PHPStan成功、独立5軸レビューでブロッカーなし。
