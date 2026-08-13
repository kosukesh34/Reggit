import { useCallback, useEffect, useRef, useState } from "react";
import { useLocation, useSearchParams } from "react-router-dom";
import { useRuntimeData } from "../context/RuntimeDataContext";
import { parseStampGrant, resolveStampGrantFromLocation, STAMP_GRANT_INVALID_QR_MESSAGE } from "../utils/stampGrant";
import { clearStampGrantCodeFromUrl } from "../../shared/utils/stampGrantToken";

export type StampGrantPhase = "idle" | "granting" | "success" | "error";

export interface StampGrantFromUrl {
  phase: StampGrantPhase;
  message: string;
  stampCardId: number | null;
  currentStamps: number | null;
  grantedCount: number;
  completedNow: boolean;
  cycleCount: number;
  nextStampCardId: number | null;
  nextStampCardName: string;
  animationKey: number;
  grantFromRaw: (raw: string) => Promise<boolean>;
}

export function useStampGrantFromUrl(): StampGrantFromUrl {
  const { addStamp, mode } = useRuntimeData();
  const location = useLocation();
  const [searchParams, setSearchParams] = useSearchParams();
  const handled = useRef(false);
  const [phase, setPhase] = useState<StampGrantPhase>("idle");
  const [message, setMessage] = useState("");
  const [stampCardId, setStampCardId] = useState<number | null>(null);
  const [currentStamps, setCurrentStamps] = useState<number | null>(null);
  const [grantedCount, setGrantedCount] = useState(0);
  const [completedNow, setCompletedNow] = useState(false);
  const [cycleCount, setCycleCount] = useState(0);
  const [nextStampCardId, setNextStampCardId] = useState<number | null>(null);
  const [nextStampCardName, setNextStampCardName] = useState("");
  const [animationKey, setAnimationKey] = useState(0);

  const grantFromRaw = useCallback(async (raw: string): Promise<boolean> => {
    const payload = parseStampGrant(raw);
    if (payload === null) {
      setPhase("error");
      setMessage(STAMP_GRANT_INVALID_QR_MESSAGE);
      return false;
    }
    setPhase("granting");
    setMessage("");
    try {
      const receipt = await addStamp(payload.value);
      const count = receipt.grantedCount ?? 1;
      setStampCardId(receipt.stampCardId ?? null);
      setCurrentStamps(receipt.currentStamps ?? null);
      setGrantedCount(count);
      setCompletedNow(receipt.completedNow === true);
      setCycleCount(receipt.cycleCount ?? 0);
      setNextStampCardId(receipt.nextStampCardId ?? null);
      setNextStampCardName(receipt.nextStampCardName ?? "");
      setMessage(receipt.message ?? `スタンプを${count}個追加しました`);
      setAnimationKey((current) => current + 1);
      setPhase("success");
      return true;
    } catch (caught) {
      setPhase("error");
      setMessage(caught instanceof Error ? caught.message : "スタンプを追加できませんでした");
      return false;
    }
  }, [addStamp]);

  useEffect(() => {
    if (location.pathname !== "/stamp" || mode !== "live") {
      return;
    }
    const payload = resolveStampGrantFromLocation()
      ?? parseStampGrant(searchParams.get("grant") ?? searchParams.get("g") ?? searchParams.get("stampCode") ?? "");
    if (payload === null || handled.current) {
      return;
    }
    handled.current = true;
    void grantFromRaw(payload.value).then((ok) => {
      if (ok) {
        clearStampGrantCodeFromUrl();
        setSearchParams({}, { replace: true });
      }
    });
  }, [grantFromRaw, location.pathname, mode, searchParams, setSearchParams]);

  return { phase, message, stampCardId, currentStamps, grantedCount, completedNow, cycleCount, nextStampCardId, nextStampCardName, animationKey, grantFromRaw };
}
