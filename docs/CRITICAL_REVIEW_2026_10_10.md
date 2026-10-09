# 批判的コード・アーキテクチャレビュー — 2026-10-10

今回の対象は、前回レビュー後の会員ポイント台帳・累計・Wallet・購入時抽選権の整合性、共通API認証の保存障害と、その呼び出し元です。すべてのファイルや外部連携の安全性を保証するものではありません。

## 確認した問題と修正

| 優先度 | 問題・再現結果 | 修正・PR |
| --- | --- | --- |
| P1 | 台帳のない旧会員への同時初期化で100ポイントの補填が2件作成される | 初期化がtransaction・会員ロック・最新台帳読取りを所有する。[#297](https://github.com/kosukesh34/Reggit/pull/297) |
| P1 | 外側のREPEATABLE READ snapshotが古いと、会員をロックしても使用済みポイントを読めず過剰消費できる | 書込み経路のrepository mappingと残高再計算をcurrent readに変更。#297 |
| P2 | afterCommitの累計加算が同時付与で上書きされる。取消→付与のcallback順で正50が100になる | 会員ロックの下で最新台帳から累計を再計算する一つの経路に統一。[#298](https://github.com/kosukesh34/Reggit/pull/298) |
| P2 | Wallet通知の残高修復が並行付与後の150を古い100で上書きする。summaryは失効・使用済みを含むcacheを表示する | 通知から残高書込みを削除し、summaryは既存の台帳由来表示残高を使う。[#299](https://github.com/kosukesh34/Reggit/pull/299) |
| P2 | 別購入の同時実行で1日1回の抽選権が2件付与される。古いsnapshotでも再付与し、複数ゲームの途中失敗で一部だけ残る | 付与全体をtransactionと会員ロックで直列化し、日次・source確認をcurrent readに変更。抽選playも会員→権利の順にロックする。独立レビューで見つかったクーポン発行との逆順も解消する。sourceのcurrent readを購入に限定してrepeatスタンプの逆順も防ぐ |
| P2 | localStorage getter拒否でLINE認証APIが送信前に停止。端末token保存の拒否・容量不足は例外となり認証tokenを保持できない | getterを既存のtry内に置き、保存失敗時のみproject/device単位で最新tokenをメモリに保持。[#300](https://github.com/kosukesh34/Reggit/pull/300) |

## アーキテクチャ判断

主な問題は、台帳初期化・累計更新・Wallet通知・抽選付与が呼び出し側のtransactionや実行順に暗黙依存していたことです。今回の変更は、不変条件を持つ既存モジュール自身に必要なロックとtransactionを置きます。Walletは通知と読取りに責務を絞り、ポイント修復を所有しません。

DomainのMemberRepository interface、project/storeのアクセス制約、外部通知のafterCommitタイミングを維持しました。大きなファイルという理由だけの分割、意図的な薄いAdmin UseCaseの削除、新たな依存やschema変更は行っていません。会員読取り時のSmaregi同期は既存の鮮度契約を持つため、この修正から取り除いていません。

## 検証記録

- #297: 修正前の同時初期化・古いsnapshotを隔離MySQLで再現。修正後のローカルcore全体1,371 tests / 6,024 assertions成功。Pint全1,640 files、PHPStan成功。最新HEADのtest/lint/main成功後にmerge scriptでマージ済み。
- #298: 関連25 tests / 101 assertions、core全体1,375 tests / 6,041 assertions成功。Pint、PHPStan成功。会員・台帳ロックを外すmutationでは並行再計算が正170に対して100となり失敗し、復元後成功。
- #299: 修正前に並行cache上書き・通知の副作用・失効残高表示の3件が失敗。修正後はWallet/抽選/Smaregi関連19 tests / 67 assertions成功。Pint、PHPStan成功。
- 抽選権: 修正前に同時購入・古いsnapshot・途中失敗の3件を再現。修正後の関連82 tests / 244 assertions成功。会員ロックを外すmutationは1権利に対し2件となり失敗。追加の独立レビューで当選playとrepeatスタンプ再到達の逆順をそれぞれSQLSTATE 1213で再現し、playの会員先ロックと購入限定のcurrent readで解消。最終の関連84 tests / 260 assertions成功。最終backend snapshot 6684f5d98のローカルcore全体1,383 tests / 6,080 assertions成功（264.50秒）。Pint、PHPStan成功。

- #300: getter拒否/端末token保存で修正前の3件の失敗を実ソースbundleで再現。修正後はAPI回帰8件、フロントエンド全unit59件、ESLint、TypeScript、Vite build成功。メモリ保存を外すmutationは3件が失敗し、空または旧tokenを送る退行を検出。独立レビューで阻害指摘なし。

テストは独立connection/forkと明示的barrierを用い、偶然のsleep順序に依存しません。並行テストにはMySQLのPROCESS権限とpcntlが必要です。専用DB・ネットワーク隔離・scratchソース・read-only依存・資源上限で実行し、既存アプリやDBは使用していません。

PRのマージは、各最新HEADのtest・lint・mainとその他チェック完了を確認し、`scripts/merge-pr.sh`で行います。GitHubプランがbranch protectionに対応しないため、スクリプトのゲートを維持します。

## 未確認事項と残る設計上の制約

afterCommit callbackにはプロセス停止後の永続的再試行を追加していません。累計再計算は会員の台帳全件を読むため、大量台帳の本番負荷は未測定です。これらは今回再現した競合とは別の検証課題です。

実Smaregi接続group、LINE/Stripe/Walletの実配信、実機、ブラウザーE2E、本番負荷は今回未検証です。外部groupはローカルcoreから明示的に除外しています。E2EをActionsへ追加・実行していません。API認証ヘッダー・HTML描画境界はソースを確認し、共有APIクライアントの保存障害を修正しました。React component自体の変更はありません。保存不可時の端末token fallbackはページ再読込みまで有効で、永続化の代替ではありません。完全なセキュリティ監査や全コードの無欠陥保証はしていません。

実際に適用したスキル: i-have-adhd、ponytail full、code-review-and-quality、improve-codebase-architecture、codebase-design、laravel-best-practices、vercel-react-best-practices（Vite/Reactのソース確認と共通API修正）、security-audit（安全な検証・レビュー指針）。

## 追加レビュー: 自動クーポン発行

誕生日クーポンの年1回判定は、同時実行と外側transactionの古いsnapshotの両方で1件ではなく2件の発行を再現しました。[#302](https://github.com/kosukesh34/Reggit/pull/302)は発行全体のtransaction・会員ロック・発行履歴のcurrent readを所有します。ローカルcoreは1,386 tests / 6,089 assertions、Pint全1,644 files、PHPStanが成功しました。会員ロックを除くmutationでは並行発行が2件になり失敗し、復元後の3 tests / 9 assertionsは成功しました。

同じ問題を初回登録の自動発行でも再現しました。並行実行、外側snapshot、複数クーポンの途中失敗の3件が修正前に失敗します。共通の自動発行処理にtransaction・会員ロックを置き、同じクーポンの発行履歴をcurrent readで確認します。手動発行の繰り返し許可と、初回注文の資格判定は維持します。並行テストを発行方法ごとのdata providerで共有し、検証コードの重複を増やしません。

設計レビューではOrderStatusServiceの完了注文スキャンと注文→会員ロックの間に循環する待ちを確認しました。既存の3回retryが緩和しますが、retry枯渇による利用者の失敗は今回未再現です。単にスキャンのロックを外すと、古いsnapshotで2件目を初回と誤判定するため採用していません。初回完了の記録を会員ロック下で所有する設計が候補です。OrderRewardServiceの公開操作もtransaction・注文ロックを呼び出し元に依存しますが、既存endpointを追跡した範囲ではこれらの制約を守っています。直接呼び出しの危険性を本番endpointの不具合として扱っていません。

この追加レビューはクーポン・注文特典の経路と呼び出し元が対象です。CLI/DB変更のためブラウザーE2Eは実行していません。外部API、実機、本番負荷、全ファイルの無欠陥は未検証です。専用DB・外部ネットワーク遮断・scratchソース・read-only依存・資源上限で検証しました。
