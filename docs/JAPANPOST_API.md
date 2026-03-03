# 日本郵便 郵便番号API 利用メモ

## 送信元IP（固定IPアドレス）について

日本郵便APIへリクエストするとき、**日本郵便側に見えるIP**は「Laravel が動いているサーバー（またはPC）の**送信元（外向き）IP**」です。

- **ngrok** は「外からあなたのアプリへ来るアクセス」用なので、**日本郵便APIから見えるIPには関係しません**。
- 日本郵便の「システム情報」に登録する**固定IPアドレス**には、**Laravel を動かしているマシンからインターネットに出るときのIP**を登録する必要があります。
  - 自宅PCで `php artisan serve` している → そのPCの回線の公衆IP
  - レンタルサーバーやVPSで動かしている → そのサーバーの固定IP（例: 162.120.184.21）

### APP_URL（ngrok）の IP を確認したい場合

別のサービス（コールバック先の許可など）で「ngrok 側の IP アドレス」の登録が必要なときは、次のコマンドで APP_URL のホスト名を解決した IP を表示できます。

```bash
php artisan app:show-ngrok-ip
```

ログにも出力する場合: `php artisan app:show-ngrok-ip --log`（`storage/logs/laravel.log` に追記されます）。

### 送信元IPの確認方法

**Laravel や ngrok を動かしている同じマシン**で、次を実行すると「日本郵便APIに見えているはずのIP」が表示されます。

```bash
./scripts/test-japanpost-api.sh
```

スクリプトの先頭で「送信元（外向き）IP: xxx.xxx.xxx.xxx」を出します。  
そのIPを日本郵便の「固定IPアドレス」に登録（または許可）すると、403 が解消される場合があります。

## 環境確認・疎通テスト

`.env` の設定と、日本郵便APIへ直接リクエストできるか確認するには:

```bash
./scripts/test-japanpost-api.sh
```

- `.env` の `JAPANPOST_API_HOST` / `JAPANPOST_CLIENT_ID` / `JAPANPOST_CLIENT_SECRET` を読み、トークン取得 → 郵便番号検索を curl で実行します。
- トークン取得に失敗すると「住所の取得ができませんでした」になります。

## ホストと認証

| 環境 | ホスト | 備考 |
|------|--------|------|
| スタブ（テスト） | `stub-qz73x.da.pf.japanpost.jp` | 一部の郵便番号のみ。スタブ用のクライアントID/シークレットが必要。 |
| 本番 | `api.da.pf.japanpost.jp` | 全郵便番号。Biz 登録後の本番用クライアントID/シークレットが必要。 |

## 403「登録情報の不一致です」が出る場合

本番ホスト（`api.da.pf.japanpost.jp`）でトークン取得時に 403 になる場合:

1. **クライアントID・シークレット**  
   本番用のものか、スタブ用のものか確認。本番ホストには本番用の認証情報が必要です。
2. **契約・利用開始**  
   「郵便番号・デジタルアドレス for Biz」で API 利用が有効になっているか確認。
3. **IP 制限（固定IPアドレス）**  
   日本郵便の「システム情報」に登録した固定IP（例: 162.120.184.21）と、**実際にAPIへリクエストを送っているマシンの送信元IP**が一致している必要があります。  
   送信元IPは `./scripts/test-japanpost-api.sh` の先頭で確認できます。開発PCや別サーバーから呼んでいる場合は、そのIPを日本郵便側に登録するか、登録済みの固定IPのマシンでLaravelを動かしてください。
4. **一時的にスタブで試す**  
   `.env` をスタブに戻して動作確認する例:
   - `JAPANPOST_API_HOST=stub-qz73x.da.pf.japanpost.jp`
   - `JAPANPOST_CLIENT_ID=Biz_DaPfJapanpost_MockAPI_j3QKS`
   - `JAPANPOST_CLIENT_SECRET=uXuN0ejHG7nAn89AfAwa`  
   スタブは一部の番号（例: 1000001）のみ検索可能です。

## 参考

- [郵便番号・デジタルアドレス for Biz](https://guide-biz.da.pf.japanpost.jp/)
- [API リファレンス](https://biz.da.pf.japanpost.jp/apireference/digiadd-swagger-biz.html)
