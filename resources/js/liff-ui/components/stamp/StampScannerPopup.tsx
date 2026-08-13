import jsQR from 'jsqr';
import { X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { parseStampGrant, STAMP_GRANT_INVALID_QR_MESSAGE } from '../../utils/stampGrant';

interface StampScannerPopupProps {
  open: boolean;
  onClose: () => void;
  onDetected: (value: string) => void;
}

let videoEl: HTMLVideoElement | null = null;
let currentStream: MediaStream | null = null;
let lastCameraOk = false;
let drawFrame = 0;
let scanTimer: number | undefined;
const scanCanvas = typeof document === 'undefined' ? null : document.createElement('canvas');

function stopTracks(): void {
  currentStream?.getTracks().forEach((track) => track.stop());
  currentStream = null;
}

function removeVideo(): void {
  stopTracks();
  if (videoEl !== null) {
    videoEl.srcObject = null;
    videoEl.remove();
    videoEl = null;
  }
}

function ensureVideo(): HTMLVideoElement {
  if (videoEl !== null && document.body.contains(videoEl)) {
    return videoEl;
  }
  const video = document.createElement('video');
  video.className = 'stamp-scanner__preload';
  video.setAttribute('playsinline', 'true');
  video.setAttribute('webkit-playsinline', 'true');
  video.setAttribute('muted', '');
  video.setAttribute('autoplay', '');
  video.setAttribute('aria-label', 'QRコード読み取りカメラ');
  video.muted = true;
  video.defaultMuted = true;
  video.autoplay = true;
  video.playsInline = true;
  document.body.appendChild(video);
  videoEl = video;
  return video;
}

function attachStream(video: HTMLVideoElement, stream: MediaStream): void {
  video.srcObject = stream;
  video.className = 'stamp-scanner__live';
  video.onloadeddata = () => {
    void video.play();
  };
}

type LegacyGetUserMedia = (
  constraints: MediaStreamConstraints,
  success: (stream: MediaStream) => void,
  error: (error: Error) => void,
) => void;

function requestUserMedia(constraints: MediaStreamConstraints): Promise<MediaStream> {
  const modern = navigator.mediaDevices?.getUserMedia;
  if (modern !== undefined) {
    return modern.call(navigator.mediaDevices, constraints);
  }
  const nav = navigator as Navigator & { getUserMedia?: LegacyGetUserMedia; webkitGetUserMedia?: LegacyGetUserMedia };
  const legacy = nav.webkitGetUserMedia ?? nav.getUserMedia;
  if (legacy === undefined) {
    return Promise.reject(new Error('unsupported'));
  }
  return new Promise((resolve, reject) => {
    legacy.call(navigator, constraints, resolve, reject);
  });
}

function startStampCamera(): Promise<boolean> {
  const video = ensureVideo();
  return requestUserMedia({ audio: false, video: { width: 640, height: 420, facingMode: 'environment' } })
    .catch(() => requestUserMedia({ audio: false, video: true }))
    .then((stream) => {
      currentStream = stream;
      attachStream(video, stream);
      lastCameraOk = true;
      return true;
    })
    .catch(() => {
      lastCameraOk = false;
      return false;
    });
}

/** クリックの同期処理から getUserMedia する。iOS の LIFF は useEffect だとカメラを拒否する。 */
export function openStampScanner(setOpen: (open: boolean) => void): void {
  ensureVideo();
  void startStampCamera().then(() => setOpen(true));
}

function stopScanLoop(): void {
  if (drawFrame !== 0) {
    window.cancelAnimationFrame(drawFrame);
    drawFrame = 0;
  }
  if (scanTimer !== undefined) {
    window.clearTimeout(scanTimer);
    scanTimer = undefined;
  }
}

function startScanLoop(onDetected: (value: string) => void, onInvalid: () => void): void {
  stopScanLoop();
  const video = videoEl;
  const canvas = scanCanvas;
  if (video === null || canvas === null) {
    return;
  }
  const context = canvas.getContext('2d');
  if (context === null) {
    return;
  }

  const canvasUpdate = (): void => {
    const width = video.videoWidth;
    const height = video.videoHeight;
    if (width > 0 && height > 0) {
      canvas.width = width;
      canvas.height = height;
      context.drawImage(video, 0, 0, width, height);
    }
    drawFrame = window.requestAnimationFrame(canvasUpdate);
  };

  let lastInvalid = '';
  const checkImage = (): void => {
    if (canvas.width <= 0 || canvas.height <= 0) {
      scanTimer = window.setTimeout(checkImage, 500);
      return;
    }
    const image = context.getImageData(0, 0, canvas.width, canvas.height);
    const code = jsQR(image.data, canvas.width, canvas.height);
    const value = code?.data?.trim();
    if (value) {
      if (parseStampGrant(value) === null) {
        if (value !== lastInvalid) {
          lastInvalid = value;
          onInvalid();
        }
        scanTimer = window.setTimeout(checkImage, 800);
        return;
      }
      onDetected(value);
      return;
    }
    scanTimer = window.setTimeout(checkImage, 500);
  };

  canvasUpdate();
  checkImage();
}

export function StampScannerPopup({ open, onClose, onDetected }: StampScannerPopupProps) {
  const onDetectedRef = useRef(onDetected);
  const [failed, setFailed] = useState(false);
  const [scanError, setScanError] = useState('');
  onDetectedRef.current = onDetected;

  useEffect(() => {
    if (!open) {
      stopScanLoop();
      removeVideo();
      setFailed(false);
      setScanError('');
      return;
    }
    if (!lastCameraOk || currentStream === null) {
      setFailed(true);
      removeVideo();
      return;
    }
    setFailed(false);
    setScanError('');
    startScanLoop(
      (value) => onDetectedRef.current(value),
      () => setScanError(STAMP_GRANT_INVALID_QR_MESSAGE),
    );
    return () => {
      stopScanLoop();
    };
  }, [open]);

  useEffect(() => () => {
    stopScanLoop();
    removeVideo();
  }, []);

  if (!open || typeof document === 'undefined') {
    return null;
  }

  return createPortal(
    <div className="stamp-scanner-root" role="dialog" aria-modal="true" aria-label="スタンプQRを読み取る">
      <button className="stamp-scanner-root__close" type="button" aria-label="閉じる" onClick={onClose}>
        <X size={19} />
      </button>
      {failed ? (
        <div className="stamp-scanner-root__error">
          <p>カメラを使用できませんでした。下のボタンから再試行するか、LINEのコードリーダーで店舗のQRを読み取ってください。</p>
          <button
            className="stamp-scanner-root__retry"
            type="button"
            onClick={() => {
              void startStampCamera().then((ok) => {
                setFailed(!ok);
                setScanError('');
                if (ok) {
                  startScanLoop(
                    (value) => onDetectedRef.current(value),
                    () => setScanError(STAMP_GRANT_INVALID_QR_MESSAGE),
                  );
                }
              });
            }}
          >
            カメラを起動する
          </button>
        </div>
      ) : (
        <>
          <p className="stamp-scanner-root__help">店舗に表示されたスタンプ付与QRを枠内に合わせてください。</p>
          {scanError !== '' ? (
            <p className="stamp-scanner-root__invalid" role="alert">{scanError}</p>
          ) : null}
        </>
      )}
    </div>,
    document.body,
  );
}
