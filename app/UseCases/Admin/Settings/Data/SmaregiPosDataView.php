<?php

namespace App\UseCases\Admin\Settings\Data;

/**
 * 仮想POSデータ確認画面用の表示データ（開発用・後で削除可）。
 *
 * POS API の一覧取得エンドポイントごとに section を1つ持つ。
 */
class SmaregiPosDataView
{
    /** @param list<array{label: string, path: string, items: array, total: int, error: ?string}> $sections */
    public function __construct(
        public readonly string $contractId,
        /** 接続エラー時のみ設定 */
        public readonly ?string $error = null,
        /** トークン失敗時のみ。.env の読み込み状況（値は出さず設定済み/未設定のみ）。null のときは非表示 */
        public readonly ?string $envCheck = null,
        /** POS 各APIの取得結果。label=表示名, path=APIパス, items=行配列, total=総数, error=取得失敗時メッセージ */
        public readonly array $sections = [],
    ) {
    }

    public function hasError(): bool
    {
        return $this->error !== null && $this->error !== '';
    }
}
