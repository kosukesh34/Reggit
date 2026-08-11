import { format } from "date-fns";

export function formatDisplayDate(value: string | null | undefined, pattern: string, fallback = "—"): string {
  const source = value?.trim() ?? "";
  if (source === "" || source === "—") {
    return fallback;
  }
  const date = new Date(source);
  if (Number.isNaN(date.getTime())) {
    return source;
  }
  return format(date, pattern);
}
