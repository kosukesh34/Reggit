import { ensureLiffDesignerModules } from '../ensureModules';

export const onboarding = {
    INDUSTRY_PRESETS: [
        { id: 'restaurant', icon: 'fa-utensils', label: 'レストラン・居酒屋', desc: '会員ポイントと注文・予約をまとめた飲食店アプリ', color: '#007aff', gradient: 'linear-gradient(135deg, #007aff, #5856d6)', patternId: 'rg-suite-loyalty' },
        { id: 'cafe', icon: 'fa-coffee', label: 'カフェ・ベーカリー', desc: 'スタンプ・モーニング・ドリンク注文のカフェアプリ', color: '#8B5E3C', gradient: 'linear-gradient(135deg, #8B5E3C, #D4A574)', patternId: 'rg-suite-cafe' },
        { id: 'fastfood', icon: 'fa-hamburger', label: 'ファストフード・テイクアウト', desc: '最短注文と進捗確認の注文OS', color: '#ff9500', gradient: 'linear-gradient(135deg, #ff9500, #ff3b30)', patternId: 'rg-suite-order' },
        { id: 'salon', icon: 'fa-cut', label: '美容室・サロン', desc: 'スタイル・予約・会員証のサロンアプリ', color: '#ff2d55', gradient: 'linear-gradient(135deg, #ff2d55, #af52de)', patternId: 'rg-suite-salon' },
        { id: 'retail', icon: 'fa-store', label: '小売・物販ショップ', desc: 'セール・ルックブック・会員先行のショップアプリ', color: '#1c1c1e', gradient: 'linear-gradient(135deg, #1c1c1e, #636366)', patternId: 'rg-suite-retail' },
        { id: 'fitness', icon: 'fa-dumbbell', label: 'フィットネス・ジム', desc: 'チェックインとリワードの会員アプリ', color: '#34c759', gradient: 'linear-gradient(135deg, #34c759, #30d158)', patternId: 'rg-suite-loyalty' },
    ],

    shouldShowOnboarding() {
        var layouts = this.state.layouts;
        var tabs = Object.keys(this.TAB_LABELS);
        for (var i = 0; i < tabs.length; i++) {
            if (Array.isArray(layouts[tabs[i]]) && layouts[tabs[i]].length > 0) return false;
        }
        return true;
    },

    showOnboarding() {
        var self = this;
        var overlay = document.createElement('div');
        overlay.className = 'ld-onboarding';
        overlay.id = 'ldOnboarding';

        var step = 1;
        var selectedIndustry = null;

        function renderStep1() {
            var cards = self.INDUSTRY_PRESETS.map(function(p) {
                return '<div class="ld-ob-card" data-industry="' + self.esc(p.id) + '">' +
                    '<div class="ld-ob-card__icon" style="background:' + p.gradient + '"><i class="fas ' + p.icon + '"></i></div>' +
                    '<div class="ld-ob-card__body">' +
                        '<div class="ld-ob-card__label">' + self.esc(p.label) + '</div>' +
                        '<div class="ld-ob-card__desc">' + self.esc(p.desc) + '</div>' +
                    '</div>' +
                    '<div class="ld-ob-card__arrow"><i class="fas fa-chevron-right"></i></div>' +
                '</div>';
            }).join('');

            overlay.innerHTML =
                '<div class="ld-ob-container">' +
                    '<div class="ld-ob-header">' +
                        '<div class="ld-ob-step-indicator">' +
                            '<span class="ld-ob-step is-active">1</span>' +
                            '<span class="ld-ob-step-line"></span>' +
                            '<span class="ld-ob-step">2</span>' +
                            '<span class="ld-ob-step-line"></span>' +
                            '<span class="ld-ob-step">3</span>' +
                        '</div>' +
                        '<button type="button" class="ld-btn ld-btn--ghost ld-ob-skip" data-ob-skip>スキップ</button>' +
                    '</div>' +
                    '<div class="ld-ob-hero-section">' +
                        '<div class="ld-ob-logo"><i class="fas fa-wand-magic-sparkles"></i></div>' +
                        '<h1 class="ld-ob-title">アプリを作成しましょう</h1>' +
                        '<p class="ld-ob-subtitle">あなたのお店に合ったテンプレートを選ぶだけで<br>プロ品質のアプリが完成します。AI知識は不要です。</p>' +
                    '</div>' +
                    '<div class="ld-ob-section-label"><i class="fas fa-store"></i> お店のジャンルを選んでください</div>' +
                    '<div class="ld-ob-cards">' + cards + '</div>' +
                    '<div class="ld-ob-footer">' +
                        '<button type="button" class="ld-btn ld-btn--ghost" data-ob-blank><i class="fas fa-file"></i> 白紙から始める</button>' +
                    '</div>' +
                '</div>';
        }

        function renderStep2() {
            var preset = self.INDUSTRY_PRESETS.find(function(p) { return p.id === selectedIndustry; });
            if (!preset) return renderStep1();

            var pattern = (self.templates || []).find(function(t) { return t.id === preset.patternId; });
            var homeBlocks = pattern && pattern.layouts && pattern.layouts.home ? pattern.layouts.home : [];
            var totalBlocks = 0;
            if (pattern && pattern.layouts) {
                Object.keys(pattern.layouts).forEach(function(tab) {
                    if (Array.isArray(pattern.layouts[tab])) totalBlocks += pattern.layouts[tab].length;
                });
            }

            var blockPreview = homeBlocks.slice(0, 5).map(function(block) {
                var bt = self.BLOCK_TYPES.find(function(b) { return b.type === (block.type || 'text'); });
                var label = bt ? bt.label : block.type;
                var icon = bt ? bt.icon : 'fa-cube';
                return '<div class="ld-ob-preview-block"><i class="fas ' + icon + '"></i> ' + self.esc(label) + '</div>';
            }).join('');

            overlay.innerHTML =
                '<div class="ld-ob-container">' +
                    '<div class="ld-ob-header">' +
                        '<div class="ld-ob-step-indicator">' +
                            '<span class="ld-ob-step is-done"><i class="fas fa-check"></i></span>' +
                            '<span class="ld-ob-step-line is-done"></span>' +
                            '<span class="ld-ob-step is-active">2</span>' +
                            '<span class="ld-ob-step-line"></span>' +
                            '<span class="ld-ob-step">3</span>' +
                        '</div>' +
                        '<button type="button" class="ld-btn ld-btn--ghost ld-ob-skip" data-ob-skip>スキップ</button>' +
                    '</div>' +
                    '<div class="ld-ob-hero-section">' +
                        '<h1 class="ld-ob-title">デザインをプレビュー</h1>' +
                        '<p class="ld-ob-subtitle">「' + self.esc(preset.label) + '」テンプレートの内容を確認してください</p>' +
                    '</div>' +
                    '<div class="ld-ob-preview-area">' +
                        '<div class="ld-ob-preview-phone">' +
                            '<div class="ld-ob-preview-phone__notch"></div>' +
                            '<div class="ld-ob-preview-phone__header" style="background:' + preset.gradient + '">ホーム</div>' +
                            '<div class="ld-ob-preview-phone__body">' + blockPreview + '</div>' +
                            '<div class="ld-ob-preview-phone__nav"><i class="fas fa-home"></i><i class="fas fa-utensils"></i><i class="fas fa-receipt"></i><i class="fas fa-user"></i></div>' +
                        '</div>' +
                        '<div class="ld-ob-preview-info">' +
                            '<div class="ld-ob-preview-info__badge" style="background:' + preset.gradient + '"><i class="fas ' + preset.icon + '"></i> ' + self.esc(preset.label) + '</div>' +
                            '<div class="ld-ob-preview-info__title">' + self.esc(pattern ? pattern.name : preset.label) + '</div>' +
                            '<div class="ld-ob-preview-info__desc">' + self.esc(pattern ? pattern.description : preset.desc) + '</div>' +
                            '<div class="ld-ob-preview-info__stats">' +
                                '<div class="ld-ob-stat"><span class="ld-ob-stat__num">' + Object.keys(self.TAB_LABELS).length + '</span><span class="ld-ob-stat__label">画面</span></div>' +
                                '<div class="ld-ob-stat"><span class="ld-ob-stat__num">' + totalBlocks + '</span><span class="ld-ob-stat__label">ブロック</span></div>' +
                                '<div class="ld-ob-stat"><span class="ld-ob-stat__num">3</span><span class="ld-ob-stat__label">デバイス対応</span></div>' +
                            '</div>' +
                            '<div class="ld-ob-preview-info__features">' +
                                '<div class="ld-ob-feature"><i class="fas fa-check-circle"></i> 全画面のレイアウトが自動設定</div>' +
                                '<div class="ld-ob-feature"><i class="fas fa-check-circle"></i> 後から自由にカスタマイズ可能</div>' +
                                '<div class="ld-ob-feature"><i class="fas fa-check-circle"></i> LINE / iOS / Android 対応</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="ld-ob-actions">' +
                        '<button type="button" class="ld-btn ld-btn--ghost" data-ob-back><i class="fas fa-arrow-left"></i> 戻る</button>' +
                        '<button type="button" class="ld-btn ld-btn--primary ld-btn--lg" data-ob-apply><i class="fas fa-magic"></i> このテンプレートを適用する</button>' +
                    '</div>' +
                '</div>';
        }

        function renderStep3() {
            overlay.innerHTML =
                '<div class="ld-ob-container ld-ob-container--compact">' +
                    '<div class="ld-ob-header">' +
                        '<div class="ld-ob-step-indicator">' +
                            '<span class="ld-ob-step is-done"><i class="fas fa-check"></i></span>' +
                            '<span class="ld-ob-step-line is-done"></span>' +
                            '<span class="ld-ob-step is-done"><i class="fas fa-check"></i></span>' +
                            '<span class="ld-ob-step-line is-done"></span>' +
                            '<span class="ld-ob-step is-active"><i class="fas fa-check"></i></span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="ld-ob-complete">' +
                        '<div class="ld-ob-complete__icon"><i class="fas fa-rocket"></i></div>' +
                        '<h1 class="ld-ob-title">準備完了！</h1>' +
                        '<p class="ld-ob-subtitle">テンプレートが適用されました。<br>次のステップでさらにカスタマイズしましょう。</p>' +
                        '<div class="ld-ob-tips">' +
                            '<div class="ld-ob-tip"><div class="ld-ob-tip__icon"><i class="fas fa-mouse-pointer"></i></div><div><strong>ブロックをクリック</strong><br>設定パネルで文字・色・画像を変更</div></div>' +
                            '<div class="ld-ob-tip"><div class="ld-ob-tip__icon"><i class="fas fa-grip-vertical"></i></div><div><strong>ドラッグで並べ替え</strong><br>ブロックの順序を自由に変更</div></div>' +
                            '<div class="ld-ob-tip"><div class="ld-ob-tip__icon"><i class="fas fa-plus-circle"></i></div><div><strong>右パネルから追加</strong><br>新しいブロックをクリックで追加</div></div>' +
                        '</div>' +
                        '<button type="button" class="ld-btn ld-btn--primary ld-btn--lg" data-ob-finish><i class="fas fa-palette"></i> カスタマイズを始める</button>' +
                    '</div>' +
                '</div>';
        }

        overlay.addEventListener('click', function(e) {
            var target = e.target as HTMLElement | null;
            if (!target) return;
            var industryCard = target.closest('[data-industry]') as HTMLElement | null;
            if (industryCard) {
                selectedIndustry = industryCard.dataset.industry;
                step = 2;
                renderStep2();
                return;
            }
            if (target.closest('[data-ob-skip]') || target.closest('[data-ob-blank]')) {
                overlay.classList.add('ld-onboarding--closing');
                setTimeout(function() { overlay.remove(); }, 300);
                return;
            }
            if (target.closest('[data-ob-back]')) {
                step = 1;
                selectedIndustry = null;
                renderStep1();
                return;
            }
            if (target.closest('[data-ob-apply]')) {
                var preset = self.INDUSTRY_PRESETS.find(function(p) { return p.id === selectedIndustry; });
                if (!preset) return;
                var pattern = (self.templates || []).find(function(t) { return t.id === preset.patternId; });
                if (pattern) {
                    self.applySuperPattern(pattern, true);
                }
                step = 3;
                renderStep3();
                return;
            }
            if (target.closest('[data-ob-finish]')) {
                overlay.classList.add('ld-onboarding--closing');
                setTimeout(function() { overlay.remove(); }, 300);
                return;
            }
        });

        renderStep1();
        document.body.appendChild(overlay);
        requestAnimationFrame(function() { overlay.classList.add('ld-onboarding--visible'); });
    },

    showHelpOverlay() {
        var self = this;
        var existing = document.getElementById('ldHelpOverlay');
        if (existing) { existing.remove(); return; }

        var overlay = document.createElement('div');
        overlay.className = 'ld-help-overlay';
        overlay.id = 'ldHelpOverlay';
        overlay.innerHTML =
            '<div class="ld-help-dialog">' +
                '<div class="ld-help-dialog__head">' +
                    '<h3><i class="fas fa-question-circle"></i> 使い方ガイド</h3>' +
                    '<button type="button" class="ld-btn ld-btn--ghost" data-close-help><i class="fas fa-times"></i></button>' +
                '</div>' +
                '<div class="ld-help-dialog__body">' +
                    '<div class="ld-help-section">' +
                        '<h4><i class="fas fa-rocket"></i> かんたん3ステップ</h4>' +
                        '<div class="ld-help-steps">' +
                            '<div class="ld-help-step"><span class="ld-help-step__num">1</span><div><strong>テンプレートを選ぶ</strong><br>左パネルのテンプレートから選択するか、「テンプレート」ボタンから業種別テンプレートを適用します。</div></div>' +
                            '<div class="ld-help-step"><span class="ld-help-step__num">2</span><div><strong>カスタマイズする</strong><br>ブロックをクリックして文字・色・画像を変更。右パネルから新しいブロックを追加できます。</div></div>' +
                            '<div class="ld-help-step"><span class="ld-help-step__num">3</span><div><strong>保存する</strong><br>「保存」ボタンを押すだけ。LIFFアプリにすぐ反映されます。</div></div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="ld-help-section">' +
                        '<h4><i class="fas fa-keyboard"></i> ショートカットキー</h4>' +
                        '<div class="ld-help-shortcuts">' +
                            '<div class="ld-help-shortcut"><kbd>Ctrl+Z</kbd> 元に戻す</div>' +
                            '<div class="ld-help-shortcut"><kbd>Ctrl+Shift+Z</kbd> やり直す</div>' +
                            '<div class="ld-help-shortcut"><kbd>Ctrl+S</kbd> 保存</div>' +
                            '<div class="ld-help-shortcut"><kbd>Delete</kbd> 選択ブロック削除</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="ld-help-section">' +
                        '<h4><i class="fas fa-th-large"></i> ブロックの種類</h4>' +
                        '<div class="ld-help-blocks">' +
                            self.BLOCK_TYPES.map(function(bt) {
                                return '<div class="ld-help-block-item"><i class="fas ' + bt.icon + '"></i> <strong>' + self.esc(bt.label) + '</strong> — ' + self.esc(bt.desc) + '</div>';
                            }).join('') +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';

        overlay.addEventListener('click', function(e) {
            var target = e.target as HTMLElement | null;
            if (e.target === overlay || (target && target.closest('[data-close-help]'))) {
                overlay.remove();
            }
        });

        document.body.appendChild(overlay);
    },
};


ensureLiffDesignerModules().onboarding = onboarding;
