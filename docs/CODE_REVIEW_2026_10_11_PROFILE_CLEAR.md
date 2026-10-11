# プロフィールの明示クリア

プロフィールの任意項目を空にすると送信本文から省略され、スマレジの旧値が残っていました。PATCH専用の本文生成で明示クリアを保持し、登録時の空値省略は維持します。住所のみを消す操作ではローカルの都道府県・市区町村・番地も消し、後続編集で旧住所が復活することを防ぎます。

検証: 修正前2件失敗・7 assertions、修正後関連33件成功・148 assertions（135.20秒）。送信失敗後のpending保持、旧inboundによる復元防止、再送成功、管理画面経由の住所クリア、構造住所との同時編集を確認。隔離ローカルDocker、外部API実接続なし。独立5軸レビューでブロッカーなし。

スマレジ側で必須に設定した項目はクリアが拒否され得ます。その場合は既存のpending再送扱いです。mail_receive_flag/customer_store_idのクリアは今回の修正対象外です。

API契約参照: https://developers.smaregi.dev/platform-api-reference/apis/pos/operations/patchcustomerscustomerid/
