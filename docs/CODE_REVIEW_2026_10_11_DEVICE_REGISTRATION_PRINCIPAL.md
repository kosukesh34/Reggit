# 端末登録の認証状態レビュー

## 必須指摘と修正

DeviceRegisterControllerはUserServiceでUserを解決してからDeviceRegisterUseCaseを呼ぶ。間に会員引き継ぎが完了すると、解決済みUserは古いauthentication_versionを保持する。DeviceServiceは現在Userを確認せず、引き継ぎで削除された旧端末を再作成し認証tokenを返せた。実UserService → 実引き継ぎ → 登録の制御した順序で再現した。

DeviceServiceの既存トランザクションでUserをproject/idで先にロックし、存在と入力時の認証世代を確認する。現在Userを保存に使い、入力モデルは変更しない。User → Deviceの順を引き継ぎ処理と揃え、トランザクションを3回まで再試行する。失効時は既存AuthenticationExceptionを使う。DeviceRegisterとQueue QRの既存呼び出し元は共通の確認を通る。新しい汎用moduleやinterfaceは追加しない。

## 検証

- 修正前：旧端末復活を1 failed / 4 assertionsで確認。
- 関連7クラス：23 passed / 128 assertions。
- 削除済みUserの作成・更新拒否、現在認証世代の正常登録も確認。
- 別DB接続で登録中のUser認証世代更新が待機し、commit後に更新可能なことを確認。
- 修正を外すと認証境界2ケース失敗、Userロックだけを外すと競合テスト失敗。
- 変更3ファイルのPint、全体PHPStan成功。最終5軸静的レビューにブロッカーなし。

同一プロセスで制御した処理順序と2DB接続の検証であり、実端末・並列HTTP負荷試験とは扱わない。外部APIへの実接続は行っていない。マージにはコミット済み最新HEADへの指定ローカル全ゲートを別途必須とする。
