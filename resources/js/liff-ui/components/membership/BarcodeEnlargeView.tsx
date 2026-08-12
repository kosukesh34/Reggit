import { MembershipBarcode } from "./MembershipBarcode";

interface BarcodeEnlargeViewProps {
  value: string;
}

/** 会員バーコードの拡大表示（中央ポップアップ内コンテンツ） */
export function BarcodeEnlargeView({ value }: BarcodeEnlargeViewProps) {
  return (
    <div className="membership-barcode-enlarge membership-barcode-enlarge--popup">
      <div className="membership-barcode-enlarge__stage">
        <MembershipBarcode value={value || "0"} enlarged />
      </div>
      <p className="membership-barcode-enlarge__hint">読み取りやすいよう、画面の明るさを上げてご提示ください。</p>
    </div>
  );
}
