export interface StampGrantPayload {
  value: string;
  kind: "signed" | "demo" | "manual";
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
  const signed = params.get("grant");
  if (signed) return { value: signed, kind: "signed" };
  const demo = params.get("stampCode");
  if (demo) return { value: demo, kind: "demo" };
  return null;
}

/** QRのURL・署名付き付与URL・手入力コードを、付与APIへ渡す値に正規化する。 */
export function parseStampGrant(rawValue: string): StampGrantPayload | null {
  const raw = rawValue.trim();
  if (raw === "") return null;

  try {
    const parsed = new URL(raw, typeof window === "undefined" ? "http://localhost" : window.location.origin);
    if (/\/api\/stamps\/grant\/\d+$/.test(parsed.pathname)) {
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
    // URLでない認証コードはこの下で扱う。
  }

  if (raw.startsWith("REGGIT-STAMP:")) return { value: raw, kind: "demo" };
  return { value: raw, kind: "manual" };
}
