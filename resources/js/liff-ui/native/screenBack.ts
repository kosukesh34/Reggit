export function screenBack(pathname: string): { to: string; label: string } | null {
  if (pathname === "/" || pathname === "/home") return null;
  if (pathname.startsWith("/more/")) return { to: "/more", label: "その他へ戻る" };
  if (pathname === "/profile") return { to: "/membership", label: "会員証へ戻る" };
  if (pathname === "/stamp/issue") return { to: "/stamp", label: "スタンプへ戻る" };
  if (["/terms", "/privacy"].includes(pathname)) return { to: "/more", label: "その他へ戻る" };
  return { to: "/home", label: "ホームへ戻る" };
}
