import {
  extractStampGrantCodeFromString,
  getStampGrantCodeFromUrl,
  isStampGrantCode,
} from "../../shared/utils/stampGrantToken";

export const STAMP_GRANT_INVALID_QR_MESSAGE = 'スタンプ付与用のQRではありません';

export interface StampGrantPayload {
  value: string;
  kind: "signed" | "demo" | "code";
}

export function createDemoStampCode(): string {
  const time = Date.now().toString(36).toUpperCase();
  const random = Math.random().toString(36).slice(2, 8).toUpperCase();
  return `REGGIT-STAMP:${time}-${random}`;
}

export function buildDemoStampUrl(code: string): string {
  if (typeof window === "undefined") return `#/stamp?stampCode=${encodeURIComponent(code)}`;
  return `${window.location.origin}${window.location.pathname}${window.location.search}#/stamp?stampCode=${encodeURIComponent(code)}`;
}

function payloadFromParams(params: URLSearchParams): StampGrantPayload | null {
  const code = params.get("g");
  if (code !== null && isStampGrantCode(code)) return { value: code.trim().toLowerCase(), kind: "code" };
  const signed = params.get("grant");
  if (signed) return { value: signed, kind: "signed" };
  const demo = params.get("stampCode");
  if (demo) return { value: demo, kind: "demo" };
  return null;
}

/** QRのURL・短縮トークン・署名付き付与URLを、付与APIへ渡す値に正規化する。 */
export function parseStampGrant(rawValue: string): StampGrantPayload | null {
  const raw = rawValue.trim();
  if (raw === "") return null;

  const fromWindowToken = extractStampGrantCodeFromString(raw);
  if (fromWindowToken !== null) return { value: fromWindowToken, kind: "code" };

  try {
    const parsed = new URL(raw, typeof window === "undefined" ? "http://localhost" : window.location.origin);
    if (/\/api\/stamps\/grant\/\d+$/.test(parsed.pathname)) {
      if (typeof window !== "undefined" && parsed.origin !== window.location.origin) return null;
      return { value: raw, kind: "signed" };
    }
    const direct = payloadFromParams(parsed.searchParams);
    if (direct) return direct;
    const question = parsed.hash.indexOf("?");
    if (question >= 0) {
      const fromHash = payloadFromParams(new URLSearchParams(parsed.hash.slice(question + 1)));
      if (fromHash) return fromHash;
    }
  } catch {
    // URLでない値はこの下で扱う。
  }

  if (raw.startsWith("REGGIT-STAMP:")) return { value: raw, kind: "demo" };
  return null;
}

export function resolveStampGrantFromLocation(): StampGrantPayload | null {
  if (typeof window === "undefined") return null;
  const fromCode = getStampGrantCodeFromUrl();
  if (fromCode !== null) return { value: fromCode, kind: "code" };
  const hash = window.location.hash || "";
  const hashQuery = hash.includes("?") ? hash.slice(hash.indexOf("?") + 1) : "";
  return payloadFromParams(new URLSearchParams(window.location.search || ""))
    ?? payloadFromParams(new URLSearchParams(hashQuery));
}
