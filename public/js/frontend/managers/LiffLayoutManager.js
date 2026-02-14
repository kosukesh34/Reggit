/**
 * LiffLayoutManager
 *
 * LIFF Builder のカスタムレイアウトを描画するマネージャー。
 * app.js の _mountTab() からカスタムレイアウトがある場合に呼ばれる。
 * デフォルトデータソースのマウントは app.js 側で行う。
 */
class LiffLayoutManager {
    constructor(app) {
        this.app = app;
        this.tabSettings = (typeof window !== 'undefined' && window.__LIFF_TAB_SETTINGS__)
            ? window.__LIFF_TAB_SETTINGS__
            : {};
    }

    /**
     * カスタムレイアウト（LIFF Builder ブロック）をコンテナに描画
     */
    renderCustomLayout(container, tab, blocks) {
        if (!container || !Array.isArray(blocks)) return;

        // タブ設定を適用（背景色など）
        this._applyTabSettings(container, tab);

        // データブロックが必要な場合は追加
        const defaultSource = LiffLayoutManager.DEFAULT_TAB_SOURCES[tab];
        if (defaultSource && !this._hasDataBlock(blocks, defaultSource)) {
            blocks = [...blocks, { type: 'data', source: defaultSource }];
        }

        container.innerHTML = this._renderBlocks(blocks);
        this._bindInlineHandlers(container);
        this._mountDataSources(container);
    }

    /** 後方互換: 旧 render(tab) メソッド - app.js からは使わないが念のため */
    render(tab) {
        // 新しい app.js では _mountTab() が呼ばれるため何もしない
    }

    /* ─── 内部メソッド ─── */

    static get DEFAULT_TAB_SOURCES() {
        return {
            home: 'announcements',
            products: 'products',
            orders: 'orders',
            stamps: 'stamps',
            coupons: 'coupons',
            queue: 'queue',
            member: 'member',
            reservations: 'reservations',
            gallery: 'gallery',
        };
    }

    _hasDataBlock(blocks, source) {
        return blocks.some(b => b && b.type === 'data' && b.source === source);
    }

    _renderBlocks(blocks) {
        if (!Array.isArray(blocks) || blocks.length === 0) return '';
        return `<div class="builder-container">${blocks.map(b => this._renderBlock(b)).join('')}</div>`;
    }

    _renderBlock(block) {
        const type = block.type || 'text';
        if (block.hidden) return '';
        const title = block.title ? `<h3 class="builder-title">${DomHelper.escapeHtml(block.title)}</h3>` : '';
        const body = block.body ? `<p class="builder-body">${DomHelper.escapeHtml(block.body)}</p>` : '';
        const align = block.align || 'left';
        const style = this._buildStyle(block);
        let button = '';
        if (block.button_label) {
            let href = '#';
            const action = block.button_action || '';
            if (action === 'tab' && block.button_target) {
                href = `#${DomHelper.escapeHtml(block.button_target)}`;
            } else if (action === 'url' && block.button_target) {
                href = DomHelper.escapeHtml(block.button_target);
            } else if (block.button_url) {
                href = DomHelper.escapeHtml(block.button_url);
            }
            button = `<a class="builder-btn" href="${href}">${DomHelper.escapeHtml(block.button_label)}</a>`;
        }

        if (['data', 'collection', 'table', 'gallery'].includes(type)) {
            const source = block.source || (type === 'gallery' ? 'gallery' : '');
            return `<section class="builder-block builder-data-block align-${align}" style="${style}" data-source="${DomHelper.escapeHtml(source)}">${title}${body}</section>`;
        }
        if (type === 'label') {
            return `<section class="builder-block builder-label-block align-${align}" style="${style}">${title || '<h3 class="builder-title">ラベル</h3>'}</section>`;
        }
        if (type === 'hero') {
            const image = block.image_url ? `<img class="builder-hero-image" src="${DomHelper.escapeHtml(block.image_url)}" alt="">` : '';
            return `<section class="builder-block builder-hero align-${align}" style="${style}">${image}<div class="builder-hero-content">${title}${body}${button}</div></section>`;
        }
        if (type === 'image') {
            const image = block.image_url ? `<img class="builder-image" src="${DomHelper.escapeHtml(block.image_url)}" alt="">` : '';
            return `<section class="builder-block builder-image-block align-${align}" style="${style}">${title}${image}${body}</section>`;
        }
        if (type === 'button') {
            return `<section class="builder-block builder-button-block align-${align}" style="${style}">${title}${body}${button}</section>`;
        }
        if (type === 'list') {
            const items = Array.isArray(block.list_items)
                ? block.list_items
                : (block.list_items ? String(block.list_items).split(',') : []);
            const list = items.length
                ? `<ul class="builder-list">${items.map(i => `<li>${DomHelper.escapeHtml(i.trim())}</li>`).join('')}</ul>`
                : '';
            return `<section class="builder-block builder-list-block align-${align}" style="${style}">${title}${body}${list}</section>`;
        }
        if (type === 'slider') {
            const images = Array.isArray(block.slider_images) ? block.slider_images : [];
            const slides = images.length
                ? images.map(url => `<div class="builder-slider-slide"><img src="${DomHelper.escapeHtml(url)}" alt=""></div>`).join('')
                : '<div class="builder-slider-slide is-empty">画像を設定してください</div>';
            return `<section class="builder-block builder-slider align-${align}" style="${style}">${title}${body}<div class="builder-slider-track">${slides}</div></section>`;
        }
        if (type === 'form') {
            const fields = Array.isArray(block.form_fields) ? block.form_fields : [];
            const formFields = fields.map((field, idx) => {
                const label = DomHelper.escapeHtml(field.label || `項目${idx + 1}`);
                const placeholder = DomHelper.escapeHtml(field.placeholder || '');
                const required = field.required ? 'required' : '';
                const name = field.name || `field_${idx}`;
                if (field.type === 'textarea') {
                    return `<label class="builder-form-label">${label}<textarea name="${name}" placeholder="${placeholder}" ${required}></textarea></label>`;
                }
                const typeAttr = DomHelper.escapeHtml(field.type || 'text');
                return `<label class="builder-form-label">${label}<input type="${typeAttr}" name="${name}" placeholder="${placeholder}" ${required} /></label>`;
            }).join('');
            return `<section class="builder-block builder-form-block align-${align}" style="${style}">${title}${body}<form class="builder-form" data-action="/api/contact">${formFields}<button type="submit" class="builder-btn">${DomHelper.escapeHtml(block.form_button_label || '送信')}</button></form></section>`;
        }
        if (type === 'html') {
            return `<section class="builder-block builder-html-block align-${align}" style="${style}">${title}${block.html || ''}</section>`;
        }
        if (type === 'spacer') {
            return `<div class="builder-spacer" style="height:${block.height || 24}px;"></div>`;
        }
        return `<section class="builder-block builder-text-block align-${align}" style="${style}">${title}${body}${button}</section>`;
    }

    _buildStyle(block) {
        const styles = [];
        if (block.background_color) styles.push(`background:${block.background_color}`);
        if (block.text_color) styles.push(`color:${block.text_color}`);
        if (block.border_color) styles.push(`border-color:${block.border_color}`);
        if (block.border_width) styles.push(`border-width:${Number(block.border_width)}px`);
        if (block.border_radius) styles.push(`border-radius:${Number(block.border_radius)}px`);
        if (block.padding_y || block.padding_x) {
            const py = Number(block.padding_y || 0);
            const px = Number(block.padding_x || 0);
            styles.push(`padding:${py}px ${px}px`);
        }
        return styles.join(';');
    }

    _applyTabSettings(section, tab) {
        const settings = this.tabSettings?.[tab] || {};
        if (settings.background_color) section.style.background = settings.background_color;
        if (settings.background_image) {
            section.style.backgroundImage = `url('${settings.background_image}')`;
            section.style.backgroundSize = settings.background_size || 'cover';
            section.style.backgroundPosition = settings.background_position || 'center';
            section.style.backgroundRepeat = settings.background_repeat || 'no-repeat';
        }
    }

    _mountDataSources(container) {
        container.querySelectorAll('[data-source]').forEach(el => {
            const source = el.getAttribute('data-source');
            if (!source) return;
            if (source === 'products') {
                el.innerHTML += `
                    <div class="category-tabs" id="categoryTabs">
                        <button class="tab-btn active" data-category="all"><i class="fas fa-th-large"></i><span>すべて</span></button>
                    </div>
                    <div class="products-section"><div class="products-grid" id="productsGrid"></div></div>
                `;
                this.app._loadProducts();
                return;
            }
            if (source === 'orders') {
                el.innerHTML += '<div id="ordersSection"></div>';
                this.app.loadOrdersTab();
                return;
            }
            if (source === 'stamps') {
                el.innerHTML += '<div id="stampsSection"></div>';
                this.app.stampsManager.load();
                return;
            }
            if (source === 'coupons') {
                el.innerHTML += '<div id="couponsSection"></div>';
                this.app.couponsManager.load();
                return;
            }
            if (source === 'queue') {
                el.innerHTML += '<div id="queueSection"></div>';
                this.app.queueManager.load();
                return;
            }
            if (source === 'reservations') {
                el.innerHTML += '<div class="reservations-section" id="reservationsSection"></div>';
                this.app.checkMembershipAndLoadReservations();
                return;
            }
            if (source === 'member') {
                el.innerHTML += '<div class="member-section" id="memberSection"></div>';
                this.app.loadMemberTab();
                return;
            }
            if (source === 'gallery') {
                el.innerHTML += '<div id="gallerySection"></div>';
                this.app.galleryManager.load();
                return;
            }
            if (source === 'announcements') {
                el.innerHTML += '<div class="announcements-section" id="announcementsSection"></div>';
                this.app.announcementManager.load();
                return;
            }
        });
    }

    _bindInlineHandlers(section) {
        section.querySelectorAll('.builder-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                const href = btn.getAttribute('href');
                if (href && href.startsWith('#')) {
                    e.preventDefault();
                    const tab = href.replace('#', '');
                    if (this.app && tab) this.app.switchTab(tab);
                }
            });
        });

        section.querySelectorAll('.builder-form').forEach(form => {
            form.addEventListener('submit', async e => {
                e.preventDefault();
                const action = form.getAttribute('data-action');
                const data = {};
                new FormData(form).forEach((value, key) => { data[key] = value; });
                try {
                    const res = await fetch(action, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data),
                    });
                    if (!res.ok) throw new Error('送信に失敗しました');
                    ToastManager.success('送信しました');
                    form.reset();
                } catch (err) {
                    ToastManager.error(err.message || '送信に失敗しました');
                }
            });
        });
    }
}
