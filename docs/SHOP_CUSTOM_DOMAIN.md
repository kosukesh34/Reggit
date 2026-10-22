# ショップ独自ドメイン

PC向け公開ショップ（`/shop`）だけ、店舗の独自ドメインで公開できます。LIFF・ネイティブアプリ・管理画面は既存の Reggit ドメインのままです。

## 手順

1. DNS で店舗ドメイン（例: `shop.example.com`）の CNAME を Reggit の公開ホストへ向ける。
2. システム管理のプロジェクト編集で「ショップ独自ドメイン」にホスト名だけを入れる（`https://` は不要）。
3. TLS 証明書を発行する（ロードバランサ / Cloudflare / certbot など、既存インフラに合わせる）。
4. `https://shop.example.com/shop` でショップが開けることを確認する。ルート `/` は `/shop` へリダイレクトする。

## アプリ側の動き

- Host が `projects.custom_shop_domain` と一致するときだけ、`/shop*` をそのプロジェクトに紐づける。
- 通常の Reggit ドメインでは `/web/{project_key}/shop` を使う。
- パスキーの Relying Party ID はアクセス中の Host に合わせる（独自ドメインではそのドメインが RP ID）。

## 注意

- LIFF URL・LINE Login のコールバックは Reggit 側の `/web/{project_key}/shop/line/callback`（または独自ドメインの `/shop/line/callback`）をチャネルに登録する。
- Cookie は `Secure` / `SameSite` を本番 HTTPS 前提で運用する。
