import { Camera, CameraOff, ScanLine } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import { CenterPopup } from "../ui/CenterPopup";
import { Button } from "../ui/Button";

interface BarcodeDetectorResult {
  rawValue: string;
}

interface BarcodeDetectorInstance {
  detect: (source: CanvasImageSource) => Promise<BarcodeDetectorResult[]>;
}

type BarcodeDetectorConstructor = new (options?: { formats?: string[] }) => BarcodeDetectorInstance;

interface StampScannerPopupProps {
  open: boolean;
  onClose: () => void;
  onDetected: (value: string) => void;
}

export function StampScannerPopup({ open, onClose, onDetected }: StampScannerPopupProps) {
  const videoRef = useRef<HTMLVideoElement>(null);
  const streamRef = useRef<MediaStream>();
  const frameRef = useRef<number>();
  const detectedRef = useRef(false);
  const [status, setStatus] = useState<"starting" | "scanning" | "unsupported" | "error">("starting");
  const [manualValue, setManualValue] = useState("");

  useEffect(() => {
    if (!open) return;
    detectedRef.current = false;
    setManualValue("");
    setStatus("starting");

    const stop = () => {
      if (frameRef.current !== undefined) cancelAnimationFrame(frameRef.current);
      streamRef.current?.getTracks().forEach((track) => track.stop());
      streamRef.current = undefined;
    };

    const start = async () => {
      try {
        if (!navigator.mediaDevices?.getUserMedia) {
          setStatus("unsupported");
          return;
        }
        const stream = await navigator.mediaDevices.getUserMedia({
          audio: false,
          video: { facingMode: { ideal: "environment" } },
        });
        streamRef.current = stream;
        const video = videoRef.current;
        if (!video) return;
        video.srcObject = stream;
        await video.play();

        const Detector = (window as typeof window & { BarcodeDetector?: BarcodeDetectorConstructor }).BarcodeDetector;
        if (!Detector) {
          setStatus("unsupported");
          return;
        }
        const detector = new Detector({ formats: ["qr_code"] });
        setStatus("scanning");

        const scan = async () => {
          if (detectedRef.current || video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) {
            frameRef.current = requestAnimationFrame(scan);
            return;
          }
          try {
            const results = await detector.detect(video);
            const value = results[0]?.rawValue?.trim();
            if (value) {
              detectedRef.current = true;
              stop();
              onDetected(value);
              return;
            }
          } catch {
            // 次のフレームで再試行する。
          }
          frameRef.current = requestAnimationFrame(scan);
        };
        frameRef.current = requestAnimationFrame(scan);
      } catch {
        setStatus("error");
      }
    };

    void start();
    return stop;
  }, [onDetected, open]);

  const submitManual = () => {
    if (manualValue.trim() === "") return;
    detectedRef.current = true;
    onDetected(manualValue);
  };

  return (
    <CenterPopup open={open} onClose={onClose} title="スタンプQRを読み取る" className="stamp-scanner-popup">
      <div className="stamp-scanner">
        <div className="stamp-scanner__viewport">
          <video ref={videoRef} muted playsInline aria-label="QRコード読み取りカメラ" />
          <span className="stamp-scanner__frame"><ScanLine /></span>
          {status === "starting" ? <p><Camera size={18} />カメラを起動しています…</p> : null}
          {status === "error" ? <p><CameraOff size={18} />カメラを使用できませんでした</p> : null}
        </div>
        <p className="stamp-scanner__help">
          {status === "unsupported"
            ? "カメラ映像は表示できますが、この端末は自動読み取りに対応していません。QRのURLを下に貼り付けてください。"
            : "店舗に表示されたスタンプ付与QRを枠内に合わせてください。"}
        </p>
        <div className="stamp-scanner__manual">
          <label htmlFor="stamp-qr-value">URL・認証コードを入力</label>
          <input id="stamp-qr-value" value={manualValue} onChange={(event) => setManualValue(event.target.value)} placeholder="QRのURLを貼り付け" />
          <Button variant="outline" disabled={manualValue.trim() === ""} onClick={submitManual}>入力内容を確認</Button>
        </div>
      </div>
    </CenterPopup>
  );
}
