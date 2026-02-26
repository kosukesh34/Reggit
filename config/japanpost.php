<?php

/**
 * 日本郵便 郵便番号・デジタルアドレスAPI
 *
 * - スタブ（テスト用）: stub-xxxx.da.pf.japanpost.jp … 一部の郵便番号のみ対応
 * - 本番（全件）: biz.da.pf.japanpost.jp … 全郵便番号を検索可能（要・本番用クライアントID/シークレット）
 *
 * 本番利用は「郵便番号・デジタルアドレス for Biz」でアカウント登録し、
 * 本番のクライアントID・シークレットを取得してください。
 * https://guide-biz.da.pf.japanpost.jp/
 */
return [
    'host' => trim((string) (env('JAPANPOST_API_HOST') ?? '')),
    'client_id' => trim((string) (env('JAPANPOST_CLIENT_ID') ?? '')),
    'client_secret' => trim((string) (env('JAPANPOST_CLIENT_SECRET') ?? '')),
];
