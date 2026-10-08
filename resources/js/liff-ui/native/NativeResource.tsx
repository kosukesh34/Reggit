import { useCallback, useEffect, useState, type ReactNode, type SetStateAction } from "react";
import { ApiClient } from "../../shared/utils/apiClient";
import { ensureLiffIdentityForLoad } from "../../frontend/demo/ensureIdentity";
import { isDemoDataMode } from "../../frontend/demo/demoMode";

import { unwrapNativeResponse } from "./nativeResponse";

const client = new ApiClient();
export class NativeRequestError extends Error {
  readonly fieldErrors: Record<string, string>;
  constructor(message: string, fields: Record<string, string | string[]> = {}, readonly status?: number) {
    super(message);
    this.fieldErrors = Object.fromEntries(Object.entries(fields).map(([key, value]) => [key, Array.isArray(value) ? value.join("\n") : value]));
  }
}
export async function nativeRequest<T>(path: string, body?: Record<string, unknown>, method: "POST" | "PUT" = "POST"): Promise<T> {
  await ensureLiffIdentityForLoad({ waitNativeDevice: true });
  const result = body === undefined ? await client.get(path) : method === "PUT" ? await client.put(path, body) : await client.post(path, body);
  if (result.success === false) throw new NativeRequestError(result.error || "通信に失敗しました", result.fieldErrors, result.status);
  return unwrapNativeResponse<T>(result.data);
}
const demoResources = new Map<string, unknown>();
export function useNativeResource<T>(path: string, sample: T, demo = isDemoDataMode()) {
  const key = `${window.__PROJECT_KEY__ ?? "demo"}:${path}`;
  const [data, updateData] = useState<T>(() => {
    if (!demo) return sample;
    if (!demoResources.has(key)) demoResources.set(key, sample);
    return demoResources.get(key) as T;
  });
  const setData = useCallback((value: SetStateAction<T>) => updateData((current) => {
    const next = typeof value === "function" ? (value as (current: T) => T)(current) : value;
    if (demo) demoResources.set(key, next);
    return next;
  }), [demo, key]);
  const [loading, setLoading] = useState(!demo);
  const [error, setError] = useState<string>();
  const reload = useCallback(async () => {
    if (demo) { setLoading(false); return; }
    setLoading(true); setError(undefined);
    try { setData(await nativeRequest<T>(path)); }
    catch (caught) { setError(caught instanceof Error ? caught.message : "読み込めませんでした"); }
    finally { setLoading(false); }
  }, [path, demo, setData]);
  useEffect(() => {
    void reload();
    const update = () => { void reload(); };
    document.addEventListener("liff-member-reload", update);
    document.addEventListener("liff-wallet-summary-updated", update);
    return () => {
      document.removeEventListener("liff-member-reload", update);
      document.removeEventListener("liff-wallet-summary-updated", update);
    };
  }, [reload]);
  return { data, setData, loading, error, reload };
}
export function NativeState({ loading, error, empty = false, title = "データはありません", subtitle, icon = "fa-circle", reload, children }: {
  loading: boolean; error?: string; empty?: boolean; title?: string; subtitle?: string; icon?: string; reload?: () => void; children: ReactNode;
}) {
  if (loading) return <div className="native-state" role="status"><span className="native-spinner" /><span>読み込み中…</span></div>;
  if (error || empty) return <div className="native-state" role={error ? "alert" : undefined}><i className={`fa-solid ${error ? "fa-wifi" : icon}`} aria-hidden="true" /><h2>{error ? "読み込めませんでした" : title}</h2><p>{error || subtitle}</p>{error && reload ? <button className="native-button" onClick={reload}>再読み込み</button> : null}</div>;
  return children;
}
export function NativeSegments({ labels, selected, onSelect }: { labels: string[]; selected: number; onSelect: (index: number) => void }) {
  return <div className="native-segments" role="tablist">{labels.map((label, index) => <button type="button" role="tab" aria-selected={selected === index} className={selected === index ? "is-active" : ""} onClick={() => onSelect(index)} key={label}>{label}</button>)}</div>;
}
export function nativeDate(value?: string) {
  if (!value) return "";
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString("ja-JP", { year: "numeric", month: "2-digit", day: "2-digit", hour: "2-digit", minute: "2-digit" });
}
