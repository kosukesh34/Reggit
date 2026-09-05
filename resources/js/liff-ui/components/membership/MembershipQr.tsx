import { useEffect, useState } from "react";
import QRCode from "qrcode";

export function MembershipQr({ value }: { value: string }) {
  const [src, setSrc] = useState("");

  useEffect(() => {
    if (value === "") {
      setSrc("");
      return;
    }
    let cancelled = false;
    void QRCode.toDataURL(value, {
      width: 280,
      margin: 1,
      color: { dark: "#1a120e", light: "#ffffff" },
    }).then((next) => {
      if (!cancelled) {
        setSrc(next);
      }
    }).catch(() => {
      if (!cancelled) {
        setSrc("");
      }
    });
    return () => {
      cancelled = true;
    };
  }, [value]);

  if (src === "") {
    return null;
  }
  return <img className="membership-qr" src={src} alt={`会員番号 ${value} のQRコード`} />;
}
