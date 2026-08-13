import { Camera, ScanLine } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import { CenterPopup } from "../ui/CenterPopup";

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

  useEffect(() => {
    if (!open) return;
    detectedRef.current = false;
    setStatus("starting");

    const stop = () => {
      if (frameRef.current !== undefined) cancelAnimationFrame(frameRef.current);
      streamRef.current?.getTracks().forEach((track) => track.stop());
      streamRef.current = undefined;
    };

    const start = async () => {
      const Detector = (window as typeof window & { BarcodeDetector?: BarcodeDetectorConstructor }).BarcodeDetector;
      if (!Detector) {
        setStatus("unsupported");
        return;
      }
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

  return (
    <CenterPopup open={open} onClose={onClose} title="スタンプQRを読み取る" className="stamp-scanner-popup">
      <div className="stamp-scanner">
        {status === "unsupported" || status === "error" ? (
          <p className="stamp-scanner__help">
            {status === "error"
              ? "カメラを使用できませんでした。LINEのコードリーダーで店舗のQRを読み取ってください。"
              : "この端末ではアプリ内カメラに対応していません。LINEのコードリーダーで店舗のQRを読み取ってください。"}
          </p>
        ) : (
          <>
            <div className="stamp-scanner__viewport">
              <video ref={videoRef} muted playsInline aria-label="QRコード読み取りカメラ" />
              <span className="stamp-scanner__frame"><ScanLine /></span>
              {status === "starting" ? <p><Camera size={18} />カメラを起動しています…</p> : null}
            </div>
            <p className="stamp-scanner__help">店舗に表示されたスタンプ付与QRを枠内に合わせてください。</p>
          </>
        )}
      </div>
    </CenterPopup>
  );
}
