import React from 'react';
import { BLOCK_TYPES } from '../catalog/blockCatalog';
import { useDesigner } from '../state/DesignerContext';

export function HelpModal({ open, onClose }: { open: boolean; onClose: () => void }) {
    const { state } = useDesigner();

    if (!open) return null;

    const isSimple = state.editorMode === 'simple';

    return (
        <div className="ld-help-overlay" onClick={onClose}>
            <div className="ld-help-dialog" onClick={(e) => e.stopPropagation()}>
                <div className="ld-help-dialog__head">
                    <h3><i className="fas fa-question-circle" /> 使い方ガイド</h3>
                    <button type="button" className="ld-btn ld-btn--ghost" onClick={onClose}>
                        <i className="fas fa-times" />
                    </button>
                </div>
                <div className="ld-help-dialog__body">
                    <div className="ld-help-section">
                        <h4><i className="fas fa-rocket" /> かんたん3ステップ</h4>
                        <div className="ld-help-steps">
                            <div className="ld-help-step">
                                <span className="ld-help-step__num">1</span>
                                <div>
                                    <strong>画面を選ぶ</strong><br />
                                    左の画面一覧で編集するタブを選びます。表示・非表示や順番・名前もここで変更できます。トップバーの「テンプレート」から一括で始めることもできます。
                                </div>
                            </div>
                            <div className="ld-help-step">
                                <span className="ld-help-step__num">2</span>
                                <div>
                                    <strong>内容を直す</strong><br />
                                    {isSimple
                                        ? '中央のプレビューをタップすると、下からシートが開きテキスト・画像・ボタン・色を変更できます。右パネルでは画面の背景色などを設定し、「テーマを変更」で全体の見た目を調整できます。'
                                        : '右パネルからブロックを追加・並べ替えし、中央のプレビューをクリックして詳細を編集します。テーマは「テーマを変更」ボタンから開けます。'}
                                </div>
                            </div>
                            <div className="ld-help-step">
                                <span className="ld-help-step__num">3</span>
                                <div>
                                    <strong>保存する</strong><br />
                                    右上の「保存」を押すと LIFF アプリに反映されます。保存後もページはリロードされません。
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className="ld-help-section">
                        <h4><i className="fas fa-sliders-h" /> 表示モード</h4>
                        <p className="ld-help-text">
                            通常は<strong>シンプル表示</strong>です。ブロックの追加・削除・並べ替えが必要なときは、トップバーの「詳細編集を開く」から詳細モードに切り替えられます。
                        </p>
                    </div>
                    <div className="ld-help-section">
                        <h4><i className="fas fa-keyboard" /> ショートカット</h4>
                        <div className="ld-help-shortcuts">
                            <div className="ld-help-shortcut"><kbd>Ctrl+Z</kbd> 元に戻す</div>
                            <div className="ld-help-shortcut"><kbd>Ctrl+Shift+Z</kbd> やり直す</div>
                            <div className="ld-help-shortcut"><kbd>Ctrl+S</kbd> 保存</div>
                            {isSimple ? null : (
                                <>
                                    <div className="ld-help-shortcut"><kbd>Delete</kbd> ブロック削除</div>
                                </>
                            )}
                            <div className="ld-help-shortcut"><kbd>Esc</kbd> パネル・モーダルを閉じる</div>
                        </div>
                    </div>
                    <div className="ld-help-section">
                        <h4><i className="fas fa-th-large" /> ブロックの種類</h4>
                        <div className="ld-help-blocks">
                            {BLOCK_TYPES.map((bt) => (
                                <div key={bt.type} className="ld-help-block-item">
                                    <i className={`fas ${bt.icon}`} />
                                    <strong>{bt.label}</strong>
                                    {' — '}
                                    {bt.desc}
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
