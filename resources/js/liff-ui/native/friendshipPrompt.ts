/** LINE公式アカウントの友だち追加を勧めるかの判定と、追加画面の表示。画面に依存しない。 */

export type FriendshipLiff = {
  isLoggedIn?: () => boolean;
  isInClient?: () => boolean;
  getFriendship?: () => Promise<{ friendFlag: boolean }>;
  requestFriendship?: () => Promise<void>;
};

/** 友だち追加していない人とブロック中の人は friendFlag が false になる。判定できないときは勧めない */
export async function needsFriendshipPrompt(liff: FriendshipLiff | undefined): Promise<boolean> {
  if (liff?.getFriendship === undefined || liff.isLoggedIn?.() !== true) {
    return false;
  }
  // 案内が出ない原因（連携先の公式アカウント違い・未連携）を後から追えるよう結果を残す
  const debug = globalThis.window?.__clientDebugLog;
  try {
    const { friendFlag } = await liff.getFriendship();
    debug?.("liff_friendship_checked", { friendFlag });
    return friendFlag === false;
  } catch (error) {
    // LIFF アプリが公式アカウントと連携していないと失敗する
    debug?.("liff_friendship_failed", { message: error instanceof Error ? error.message : String(error) });
    return false;
  }
}

/** LINE の友だち追加・ブロック解除の小窓を開く。LINE 外や、画面サイズが Full でない LIFF では開けず false を返す */
export async function openFriendshipWindow(liff: FriendshipLiff | undefined): Promise<boolean> {
  if (liff?.requestFriendship === undefined || liff.isInClient?.() !== true) {
    return false;
  }
  try {
    await liff.requestFriendship();
    return true;
  } catch {
    return false;
  }
}
