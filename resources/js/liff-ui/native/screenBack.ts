export function screenBack(pathname: string): { to: string; label: string } | null {
  if (pathname.startsWith("/more/")) return { to: "/more", label: "その他へ戻る" };
  if (pathname === "/stamp/issue") return { to: "/stamp", label: "スタンプへ戻る" };
  if (["/terms", "/privacy"].includes(pathname)) return { to: "/more", label: "その他へ戻る" };
  // タブのルート画面には戻る操作を表示しない。詳細・フォーム内の戻る操作は NativeNavigation が担当する。
  return null;
}
