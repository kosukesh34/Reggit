# 引き継ぎ後の古い認証による書き込み

## 再現済みの必須指摘

UserServiceで認証した後に会員引き継ぎが完了すると、source User IDとMemberは維持され、authentication_versionが上がる。取得済みUserによるMemberService.updateProfileはownerが変わっていないため保存でき、ReservationCancelUseCaseも古い会員情報で取消できた。実UserServiceの端末認証→実Issue/Claim→取得済みUserの操作で両方を再現した。

## 修正

profileは既存の現在Userロック下で入力の認証世代を照合し、既存ValidationExceptionで保存前に拒否する。予約取消は既存UseCase内のトランザクションで現在User→Member→Reservationをロックし、存在・認証世代・現在会員資格を確認してfresh UserをServiceへ渡す。失効時は既存requiresMembership出力を返す。管理画面の直接Service取消は変更しない。新しい汎用module/interfaceは追加せず、既存の書き込みinterfaceが認証と保存の整合性を保つ。

## 検証

- 修正前の実引き継ぎ2ケース: 2 failed / 8 assertions。
- 現在世代での正常profile更新・取消、削除済みcached Memberの取消拒否を含む関連6クラス: 32 passed / 125 assertions。
- 一時コピーで両修正を外すと3 failed / 2 passed。正常世代の操作は成功し、拒否すべき操作が検出される。
- Pint変更3ファイル・全体PHPStan成功、最終5軸レビューにブロッカーなし。

制御した取得→引き継ぎ→書き込み順序の検証で、並列HTTP負荷・実端末・本番・外部実接続は未確認。マージには最新コミットへの全ローカルゲートを別途実行する。
