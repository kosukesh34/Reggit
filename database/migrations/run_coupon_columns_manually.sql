-- クーポンに description, use_display, smaregi_coupon_id を追加する
-- マイグレーションが実行できない場合に、このSQLをMySQLで実行してください。
-- 既にカラムがある場合は "Duplicate column name" エラーになるので、その行だけスキップしてください。

ALTER TABLE `coupons` ADD COLUMN `description` TEXT NULL COMMENT '会員に表示する説明文';
ALTER TABLE `coupons` ADD COLUMN `use_display` VARCHAR(20) NOT NULL DEFAULT 'none' COMMENT '使用時表示: none=なし, barcode=バーコード, qr=QRコード';
ALTER TABLE `coupons` ADD COLUMN `smaregi_coupon_id` VARCHAR(64) NULL COMMENT 'スマレジPOSに登録したクーポンID（同期用）';
