import { initHelpTips } from './helpTips';

/** 管理画面のレイアウトを使わない画面（プロジェクト作成のオンボーディングなど）用の入口 */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHelpTips);
} else {
    initHelpTips();
}
