/** LINE公式アカウントの友だち追加を勧めるかの判定。画面に依存しない。 */

export type FriendshipLiff = {
  isLoggedIn?: () => boolean;
  getFriendship?: () => Promise<{ friendFlag: boolean }>;
};

/** 友だち追加していない人とブロック中の人は friendFlag が false になる。判定できないときは勧めない */
export async function needsFriendshipPrompt(liff: FriendshipLiff | undefined, friendAddUrl: string): Promise<boolean> {
  if (!friendAddUrl.startsWith("https://") || liff?.getFriendship === undefined || liff.isLoggedIn?.() !== true) {
    return false;
  }
  try {
    return (await liff.getFriendship()).friendFlag === false;
  } catch {
    // LIFF アプリが公式アカウントと連携していないと失敗する
    return false;
  }
}
