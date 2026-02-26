/**
 * LiffLayoutManager
 *
 * LIFF Builder のカスタムレイアウトを描画するマネージャー。
 * admin の LIFF Designer で保存したブロック配列をユーザー画面に反映する。
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
        this._applyTabSettings(container, tab);

        const defaultSource = LiffLayoutManager.DEFAULT_TAB_SOURCES[tab];
        if (defaultSource && !this._hasDataBlock(blocks, defaultSource)) {
            blocks = [...blocks, { type: 'data', source: defaultSource }];
        }

        container.innerHTML = this._renderBlocks(blocks);
        this._bindInlineHandlers(container);
        this._initSliders(container);
        this._mountDataSources(container);
    }

    /**
     * タブ切替時にカスタムレイアウトを描画する。
     */
    render(tab) {
        const layouts = (typeof window !== 'undefined' && window.__LIFF_TAB_LAYOUTS__)
            ? window.__LIFF_TAB_LAYOUTS__
            : {};
        const blocks = layouts[tab];
        if (!Array.isArray(blocks) || blocks.length === 0) return;

        const sectionId = tab + 'Section';
        const container = document.getElementById(sectionId);
        if (!container) return;
        if (container.dataset.layoutRendered === '1') return;
        container.dataset.layoutRendered = '1';

        this.renderCustomLayout(container, tab, blocks);
    }

    /* ─── 定数 ─── */

    static get DEFAULT_TAB_SOURCES() {
        return {
            home: 'announcements', products: 'products', orders: 'orders',
            stamps: 'stamps', coupons: 'coupons', queue: 'queue',
            member: 'member', reservations: 'reservations', gallery: 'gallery',
        };
    }

    /* ─── 内部メソッド ─── */

    _hasDataBlock(blocks, source) {
        return blocks.some(b => b && b.type === 'data' && b.source === source);
    }

    _renderBlocks(blocks) {
        if (!Array.isArray(blocks) || blocks.length === 0) return '';
        return `<div class="builder-container">${blocks.map(b => this._renderBlock(b)).join('')}</div>`;
    }

    _esc(s) {
        return DomHelper.escapeHtml(s || '');
    }

    _renderBlock(block) {
        const type = block.type || 'text';
        if (block.hidden) return '';
        const esc = (s) => this._esc(s);
        const br = block.border_radius != null ? block.border_radius : 10;
        const mb = block.margin_bottom ? `margin-bottom:${block.margin_bottom}px;` : '';
        const opacity = (block.opacity !== undefined && block.opacity < 100) ? `opacity:${block.opacity / 100};` : '';
        const shadow = block.shadow ? 'box-shadow:0 4px 12px rgba(0,0,0,0.1);' : '';
        const pad = block.padding !== undefined && block.padding !== null ? `padding:${block.padding}px;` : '';
        const bgColor = block.background_color ? `background-color:${block.background_color};` : '';
        const baseStyle = `border-radius:${br}px;${mb}${opacity}${shadow}${pad}${bgColor}`;

        if (type === 'data') {
            return `<section class="builder-block builder-data-block" style="${baseStyle}" data-source="${esc(block.source || '')}"></section>`;
        }
        if (type === 'hero') {
            const bg = block.image_url
                ? `background-image:url('${esc(block.image_url)}');background-size:cover;background-position:center;`
                : `background:linear-gradient(135deg,${block.gradient_start || '#667eea'} 0%,${block.gradient_end || '#764ba2'} 100%);`;
            return `<section class="builder-block builder-hero" style="${bg}color:#fff;padding:32px 20px;text-align:center;${baseStyle}">
                <h2 style="font-size:18px;font-weight:700;margin:0 0 4px;">${esc(block.title)}</h2>
                <p style="font-size:12px;opacity:0.85;margin:0;">${esc(block.subtitle || block.text)}</p>
            </section>`;
        }
        if (type === 'heading') {
            const sizes = { small: 14, medium: 16, large: 20 };
            const fs = sizes[block.size] || 16;
            return `<section class="builder-block" style="background:#fff;padding:12px 14px;font-size:${fs}px;font-weight:700;color:${block.text_color || '#1f2937'};text-align:${block.text_align || 'left'};${baseStyle}">${esc(block.text)}</section>`;
        }
        if (type === 'text') {
            return `<section class="builder-block" style="background:#fff;padding:12px 14px;font-size:${block.font_size || 13}px;color:${block.text_color || '#4b5563'};line-height:1.6;text-align:${block.text_align || 'left'};${baseStyle}">${esc(block.text || block.body)}</section>`;
        }
        if (type === 'image') {
            const img = block.image_url
                ? `<img src="${esc(block.image_url)}" alt="${esc(block.alt)}" style="width:100%;height:auto;display:block;object-fit:${block.fit || 'cover'};">`
                : '';
            return `<section class="builder-block" style="overflow:hidden;${baseStyle}">${img}</section>`;
        }
        if (type === 'button') {
            const w = block.full_width !== false ? 'display:block;width:100%;box-sizing:border-box;' : 'display:inline-block;';
            return `<section class="builder-block" style="text-align:center;padding:8px;${baseStyle}">
                <a href="${esc(block.action) || '#'}" class="builder-btn" style="${w}padding:12px 24px;border-radius:8px;background:${block.color || '#06C755'};color:${block.text_color || '#fff'};font-size:14px;font-weight:700;text-decoration:none;text-align:center;">${esc(block.text)}</a>
            </section>`;
        }
        if (type === 'card') {
            const img = block.image_url
                ? `<img src="${esc(block.image_url)}" alt="" style="width:100%;height:${block.image_height || 140}px;object-fit:cover;display:block;">`
                : `<div style="width:100%;height:${block.image_height || 140}px;background:#f3f4f6;"></div>`;
            return `<section class="builder-block" style="background:#fff;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);${baseStyle}">
                ${img}
                <div style="padding:12px;">
                    <div style="font-size:15px;font-weight:700;color:#1f2937;margin-bottom:4px;">${esc(block.title)}</div>
                    ${block.description ? `<div style="font-size:12px;color:#6b7280;">${esc(block.description)}</div>` : ''}
                </div>
            </section>`;
        }
        if (type === 'collection') {
            return this._renderCollection(block, baseStyle);
        }
        if (type === 'slider') {
            return this._renderSlider(block, baseStyle);
        }
        if (type === 'icon_text') {
            const rows = Array.isArray(block.rows) ? block.rows : [];
            const html = rows.map(r =>
                `<div style="display:flex;align-items:center;gap:10px;font-size:13px;color:#374151;">` +
                `<i class="fas ${r.icon || 'fa-check'}" style="color:${r.color || '#06C755'};font-size:16px;flex-shrink:0;"></i>` +
                `<span>${esc(r.text)}</span></div>`
            ).join('');
            return `<section class="builder-block" style="background:#fff;padding:12px 14px;display:flex;flex-direction:column;gap:8px;${baseStyle}">${html}</section>`;
        }
        if (type === 'list') {
            const items = Array.isArray(block.items) ? block.items : [];
            const tag = block.style === 'number' ? 'ol' : 'ul';
            const listHtml = items.length > 0
                ? `<${tag} style="margin:0;padding-left:20px;font-size:13px;line-height:1.8;color:${block.text_color || '#374151'};">${items.map(i => `<li>${esc(i)}</li>`).join('')}</${tag}>`
                : '';
            return `<section class="builder-block" style="background:#fff;padding:12px 14px;${baseStyle}">${listHtml}</section>`;
        }
        if (type === 'banner') {
            return `<section class="builder-block" style="display:flex;align-items:center;gap:8px;padding:12px 14px;font-size:13px;font-weight:600;background:${block.color || '#eef2ff'};color:${block.text_color || '#374151'};${baseStyle}">
                ${block.icon ? `<i class="fas ${block.icon}" style="font-size:16px;flex-shrink:0;"></i>` : ''}
                <span>${esc(block.text)}</span>
            </section>`;
        }
        if (type === 'spacer') {
            return `<div style="height:${block.height || 16}px;${mb}"></div>`;
        }
        if (type === 'divider') {
            return `<div style="padding:8px 0;${mb}"><hr style="border:none;border-top:1px ${block.divider_style || 'solid'} ${block.divider_color || '#e5e7eb'};margin:0;"></div>`;
        }
        return `<section class="builder-block" style="background:#fff;padding:12px 14px;${baseStyle}">${esc(block.text || block.body || '')}</section>`;
    }

    _renderCollection(block, baseStyle) {
        const esc = (s) => this._esc(s);
        const items = Array.isArray(block.items) ? block.items : [];
        const isGrid = block.layout === 'grid';
        const cols = block.columns || 2;
        const gap = block.gap != null ? block.gap : 8;
        const maxRows = block.max_rows || 0;
        const visibleCount = (isGrid && maxRows > 0) ? maxRows * cols : items.length;
        const visible = items.slice(0, visibleCount);
        const containerStyle = isGrid
            ? `display:grid;grid-template-columns:repeat(${cols},1fr);gap:${gap}px;`
            : `display:flex;gap:${gap}px;overflow-x:auto;padding-bottom:4px;-webkit-overflow-scrolling:touch;`;
        const itemStyle = isGrid
            ? 'background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);'
            : `flex-shrink:0;width:${block.item_width || 140}px;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);`;
        const imgH = block.item_image_height || 100;
        const itemsHtml = visible.map(item => {
            const img = item.image_url
                ? `<img src="${esc(item.image_url)}" alt="" style="width:100%;height:${imgH}px;object-fit:cover;display:block;">`
                : `<div style="width:100%;height:${imgH}px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;color:#9ca3af;"><i class="fas fa-image"></i></div>`;
            const desc = (block.show_description && item.description)
                ? `<div style="padding:0 8px 6px;font-size:10px;color:#6b7280;">${esc(item.description)}</div>` : '';
            return `<div style="${itemStyle}">${img}<div style="padding:6px 8px;font-size:12px;font-weight:600;color:#1f2937;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(item.title)}</div>${desc}</div>`;
        }).join('');
        return `<section class="builder-block" style="${baseStyle}"><div style="${containerStyle}">${itemsHtml}</div></section>`;
    }

    _renderSlider(block, baseStyle) {
        const esc = (s) => this._esc(s);
        const slides = Array.isArray(block.slides) ? block.slides : [];
        const h = block.slide_height || 160;
        if (slides.length === 0) {
            return `<section class="builder-block" style="height:${h}px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;color:#9ca3af;${baseStyle}"><i class="fas fa-images" style="font-size:28px;"></i></section>`;
        }
        const slideHtml = slides.map((s, i) => {
            const display = i === 0 ? '' : 'display:none;';
            const img = s.image_url ? `<img src="${esc(s.image_url)}" alt="" style="width:100%;height:100%;object-fit:cover;">` : '';
            const caption = s.caption ? `<div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,0.5));color:#fff;padding:24px 12px 10px;font-size:13px;font-weight:600;">${esc(s.caption)}</div>` : '';
            return `<div class="builder-slide" data-slide-index="${i}" style="position:absolute;inset:0;${display}">${img}${caption}</div>`;
        }).join('');
        const dots = (block.show_dots !== false && slides.length > 1)
            ? `<div style="position:absolute;bottom:8px;left:50%;transform:translateX(-50%);display:flex;gap:5px;">${slides.map((_, i) => `<span class="builder-slider-dot" data-slide-index="${i}" style="width:6px;height:6px;border-radius:50%;background:${i === 0 ? '#fff' : 'rgba(255,255,255,0.4)'};cursor:pointer;transition:background 0.2s;"></span>`).join('')}</div>`
            : '';
        return `<section class="builder-block builder-slider-block" style="position:relative;overflow:hidden;height:${h}px;${baseStyle}" data-auto-play="${block.auto_play !== false ? '1' : '0'}" data-slide-count="${slides.length}">${slideHtml}${dots}</section>`;
    }

    _buildStyle(block) {
        const styles = [];
        if (block.background_color) styles.push(`background:${block.background_color}`);
        if (block.text_color) styles.push(`color:${block.text_color}`);
        if (block.border_radius) styles.push(`border-radius:${Number(block.border_radius)}px`);
        return styles.join(';');
    }

    _applyTabSettings(section, tab) {
        const settings = (this.tabSettings && this.tabSettings[tab]) ? this.tabSettings[tab] : {};
        if (settings.background_color) section.style.background = settings.background_color;
        if (settings.background_image) {
            section.style.backgroundImage = `url('${settings.background_image}')`;
            section.style.backgroundSize = settings.background_size || 'cover';
            section.style.backgroundPosition = settings.background_position || 'center';
            section.style.backgroundRepeat = settings.background_repeat || 'no-repeat';
        }
    }

    /** スライダーの自動再生とドットナビゲーションを初期化 */
    _initSliders(container) {
        container.querySelectorAll('.builder-slider-block').forEach(slider => {
            const count = parseInt(slider.dataset.slideCount, 10) || 0;
            if (count <= 1) return;
            let current = 0;
            const showSlide = (idx) => {
                slider.querySelectorAll('.builder-slide').forEach(s => { s.style.display = 'none'; });
                slider.querySelectorAll('.builder-slider-dot').forEach(d => { d.style.background = 'rgba(255,255,255,0.4)'; });
                const slide = slider.querySelector(`.builder-slide[data-slide-index="${idx}"]`);
                const dot = slider.querySelector(`.builder-slider-dot[data-slide-index="${idx}"]`);
                if (slide) slide.style.display = '';
                if (dot) dot.style.background = '#fff';
                current = idx;
            };
            slider.querySelectorAll('.builder-slider-dot').forEach(dot => {
                dot.addEventListener('click', () => showSlide(parseInt(dot.dataset.slideIndex, 10)));
            });
            if (slider.dataset.autoPlay === '1') {
                setInterval(() => { showSlide((current + 1) % count); }, 4000);
            }
        });
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
                this.app.loadProducts();
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
    }
}
