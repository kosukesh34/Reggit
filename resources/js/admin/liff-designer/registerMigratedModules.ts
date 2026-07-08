import './modules/panelDrawers';
import './modules/panelResizer';
import './modules/io';
import './modules/dragDrop';
import './modules/onboarding';
import './modules/dataPreview';
import './modules/blockDesigns';
import './modules/uiEvents';
import './modules/themePanel';
import './modules/templatePreview';
import './modules/templateManager';
import './LiffDesigner';

/** LIFF Designer の小モジュール群を window.LiffDesignerModules に登録する */
export function registerMigratedLiffDesignerModules(): void {
    // side-effect imports above register modules + LiffDesigner class
}
