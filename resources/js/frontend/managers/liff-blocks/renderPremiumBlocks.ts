/**
 * LIFF Builder ブロック — プレミアム小売アプリ風 HTML（1ブロック種別=責務分離）
 */
type Escaper = (value: unknown) => string;
type NavAttrs = { href: string; dataExternal: string };

type PremiumRenderer = {
    hero: (block: any, esc: Escaper, baseStyle: string) => string;
    heading: (block: any, esc: Escaper, baseStyle: string) => string;
    text: (block: any, esc: Escaper, baseStyle: string) => string;
    button: (block: any, esc: Escaper, baseStyle: string) => string;
    card: (block: any, esc: Escaper, baseStyle: string) => string;
    banner: (block: any, esc: Escaper, baseStyle: string) => string;
    collection: (block: any, esc: Escaper, baseStyle: string) => string;
    slider: (block: any, esc: Escaper, baseStyle: string) => string;
    memberSummaryRing: (pct: number) => string;
    memberSummaryHtml: (data: any, greeting: string, esc: Escaper, design: string) => string;
};

function liffBlockNavAttrs(action: string | undefined, esc: Escaper): NavAttrs {
    const href = action || '#';
    const isExternal = href !== '#' && (href.startsWith('http://') || href.startsWith('https://'));
    const dataExternal = isExternal ? ' data-builder-external="1"' : '';
    return { href: esc(href), dataExternal };
}

function getLiffLayoutBlocks(): any {
    if (!window.LiffLayoutBlocks) {
        window.LiffLayoutBlocks = {};
    }
    return window.LiffLayoutBlocks;
}

const premium: PremiumRenderer = {
    hero(block, esc, baseStyle) {
        const design = block.design || 'editorial';
        const bg = block.image_url ? `background-image:url('${esc(block.image_url)}');` : '';
        const grad = block.image_url ? '' : `--hero-grad-start:${block.gradient_start || 'var(--primary-color)'};--hero-grad-end:${block.gradient_end || 'var(--primary-dark)'};`;
        const eyebrow = block.eyebrow || 'Welcome';
        const cta = block.button_label || block.cta_label || '';
        const ctaHtml = cta
            ? `<span class="builder-hero__cta">${esc(cta)}</span>`
            : '';
        return `<section class="builder-block builder-hero builder-hero--${esc(design)}" style="${bg}${grad}${baseStyle}">
            <div class="builder-hero__scrim"></div>
            <div class="builder-hero__content">
                ${design !== 'minimal' ? `<span class="builder-hero__eyebrow">${esc(eyebrow)}</span>` : ''}
                <h2 class="builder-hero__title">${esc(block.title)}</h2>
                <p class="builder-hero__subtitle">${esc(block.subtitle || block.text || '')}</p>
                ${ctaHtml}
            </div>
        </section>`;
    },
    heading(block, esc, baseStyle) {
        const size = block.size || 'medium';
        const align = block.text_align || 'left';
        const design = block.design || 'plain';
        return `<section class="builder-block builder-heading builder-heading--${esc(size)} builder-heading--design-${esc(design)}" style="text-align:${esc(align)};${baseStyle}">
            <h3 class="builder-heading__text" style="${block.text_color ? `color:${esc(block.text_color)};` : ''}">${esc(block.text)}</h3>
        </section>`;
    },
    text(block, esc, baseStyle) {
        const align = block.text_align || 'left';
        const design = block.design || 'body';
        return `<section class="builder-block builder-text-block builder-text-block--design-${esc(design)}" style="text-align:${esc(align)};${baseStyle}">
            <div class="builder-text-block__body" style="font-size:${block.font_size || 14}px;${block.text_color ? `color:${esc(block.text_color)};` : ''}">${esc(block.text || block.body)}</div>
        </section>`;
    },
    button(block, esc, baseStyle) {
        const action = block.action || '#';
        const isExternal = action !== '#' && (action.startsWith('http://') || action.startsWith('https://'));
        const dataExternal = isExternal ? ' data-builder-external="1"' : '';
        const full = block.full_width !== false ? ' builder-btn--full' : '';
        const design = block.design || 'cta';
        const variantClass = design === 'outline' ? ' builder-btn--outline' : design === 'soft' ? ' builder-btn--soft' : ' builder-btn--cta';
        const colorStyle = block.color ? ` style="--btn-bg:${esc(block.color)};${block.text_color ? `--btn-fg:${esc(block.text_color)};` : ''}${design === 'outline' || design === 'soft' ? `--btn-accent:${esc(block.color)};` : ''}"` : '';
        return `<section class="builder-block builder-button-wrap" style="${baseStyle}">
            <a href="${esc(action)}" class="builder-btn${variantClass}${full}"${dataExternal}${colorStyle}>${esc(block.text)}</a>
        </section>`;
    },
    card(block, esc, baseStyle) {
        const design = block.design || 'editorial';
        const imgH = block.image_height || 160;
        const img = block.image_url
            ? `<img class="builder-editorial-card__img" src="${esc(block.image_url)}" alt="" loading="lazy">`
            : '<div class="builder-editorial-card__img builder-editorial-card__img--placeholder" aria-hidden="true"><i class="fas fa-image"></i></div>';
        const action = block.action || block.link || '';
        if (design === 'horizontal') {
            const body = `<div class="builder-editorial-card__media builder-editorial-card__media--horizontal" style="--card-img-h:${imgH}px">${img}</div>
                <div class="builder-editorial-card__body">
                    <h4 class="builder-editorial-card__title">${esc(block.title)}</h4>
                    ${block.description ? `<p class="builder-editorial-card__desc">${esc(block.description)}</p>` : ''}
                </div>`;
            if (action) {
                const nav = liffBlockNavAttrs(action, esc);
                return `<a href="${nav.href}" class="builder-block builder-editorial-card builder-editorial-card--horizontal builder-editorial-card--link builder-nav"${nav.dataExternal} style="${baseStyle}">${body}</a>`;
            }
            return `<article class="builder-block builder-editorial-card builder-editorial-card--horizontal" style="${baseStyle}">${body}</article>`;
        }
        if (design === 'overlay') {
            const body = `<div class="builder-editorial-card__media builder-editorial-card__media--overlay" style="--card-img-h:${imgH}px">${img}
                <div class="builder-editorial-card__overlay-text">
                    <h4 class="builder-editorial-card__title">${esc(block.title)}</h4>
                    ${block.description ? `<p class="builder-editorial-card__desc">${esc(block.description)}</p>` : ''}
                </div></div>`;
            if (action) {
                const nav = liffBlockNavAttrs(action, esc);
                return `<a href="${nav.href}" class="builder-block builder-editorial-card builder-editorial-card--overlay builder-editorial-card--link builder-nav"${nav.dataExternal} style="${baseStyle}">${body}</a>`;
            }
            return `<article class="builder-block builder-editorial-card builder-editorial-card--overlay" style="${baseStyle}">${body}</article>`;
        }
        if (design === 'featured') {
            const body = `<div class="builder-editorial-card__media builder-editorial-card__media--featured" style="--card-img-h:${imgH}px">${img}
                <span class="builder-editorial-card__featured-badge">Featured</span></div>
                <div class="builder-editorial-card__body">
                    <h4 class="builder-editorial-card__title">${esc(block.title)}</h4>
                    ${block.description ? `<p class="builder-editorial-card__desc">${esc(block.description)}</p>` : ''}
                </div>`;
            if (action) {
                const nav = liffBlockNavAttrs(action, esc);
                return `<a href="${nav.href}" class="builder-block builder-editorial-card builder-editorial-card--featured builder-editorial-card--link builder-nav"${nav.dataExternal} style="${baseStyle}">${body}</a>`;
            }
            return `<article class="builder-block builder-editorial-card builder-editorial-card--featured" style="${baseStyle}">${body}</article>`;
        }
        const body = `<div class="builder-editorial-card__media" style="--card-img-h:${imgH}px">${img}</div>
            <div class="builder-editorial-card__body">
                <h4 class="builder-editorial-card__title">${esc(block.title)}</h4>
                ${block.description ? `<p class="builder-editorial-card__desc">${esc(block.description)}</p>` : ''}
            </div>`;
        if (action) {
            const nav = liffBlockNavAttrs(action, esc);
            return `<a href="${nav.href}" class="builder-block builder-editorial-card builder-editorial-card--link builder-nav"${nav.dataExternal} style="${baseStyle}">${body}</a>`;
        }
        return `<article class="builder-block builder-editorial-card" style="${baseStyle}">${body}</article>`;
    },
    banner(block, esc, baseStyle) {
        const design = block.design || 'promo';
        const bg = block.color || '';
        const fg = block.text_color || '';
        let style = '';
        if (design === 'outline') style = `${bg ? `border-color:${esc(bg)};color:${esc(bg)};` : ''}`;
        else if (design === 'soft') style = `${bg ? `background:${esc(bg)}18;color:${esc(bg)};` : ''}`;
        else style = `${bg ? `background:${esc(bg)};` : ''}${fg ? `color:${esc(fg)};` : ''}`;
        const countdown = design === 'countdown'
            ? `<span class="builder-promo-banner__countdown" aria-hidden="true"><em>限定</em><strong>24h</strong></span>`
            : '';
        const inner = `${block.icon ? `<span class="builder-promo-banner__icon"><i class="fas ${esc(block.icon)}" aria-hidden="true"></i></span>` : ''}
            <span class="builder-promo-banner__text">${esc(block.text)}</span>
            ${countdown}
            <i class="fas fa-chevron-right builder-promo-banner__chev" aria-hidden="true"></i>`;
        const action = block.action || block.link || '';
        const designClass = ` builder-promo-banner--${esc(design)}`;
        if (action) {
            const nav = liffBlockNavAttrs(action, esc);
            return `<a href="${nav.href}" class="builder-block builder-promo-banner builder-promo-banner--link${designClass} builder-nav"${nav.dataExternal} style="${style}${baseStyle}">${inner}</a>`;
        }
        return `<section class="builder-block builder-promo-banner${designClass}" style="${style}${baseStyle}">${inner}</section>`;
    },
    collection(block, esc, baseStyle) {
        const items = Array.isArray(block.items) ? block.items : [];
        const design = block.design || 'product_shelf';
        if (design === 'chip_scroll') {
            const chips = items.map((item: any) => {
                const action = item.action || block.item_action || '#products';
                const nav = liffBlockNavAttrs(action, esc);
                return `<a href="${nav.href}" class="builder-chip builder-nav"${nav.dataExternal}>${esc(item.title)}</a>`;
            }).join('');
            const label = block.section_label || block.title || '';
            const head = label ? `<div class="builder-section-head"><span class="builder-section-head__label">${esc(label)}</span></div>` : '';
            return `<section class="builder-block builder-collection-wrap builder-collection-wrap--design-chip_scroll" style="${baseStyle}">${head}<div class="builder-chip-scroll">${chips}</div></section>`;
        }
        const isGrid = block.layout === 'grid';
        const cols = block.columns || 2;
        const gap = block.gap != null ? block.gap : 12;
        const maxRows = block.max_rows || 0;
        const visibleCount = (isGrid && maxRows > 0) ? maxRows * cols : items.length;
        const visible = items.slice(0, visibleCount);
        const layoutClass = isGrid ? `builder-collection--grid builder-collection--cols-${cols}` : 'builder-collection--scroll';
        const imgH = block.item_image_height || (isGrid ? 120 : 130);
        const defaultItemAction = block.item_action || '#products';
        const itemsHtml = visible.map((item: any) => {
            const action = item.action || defaultItemAction;
            const nav = liffBlockNavAttrs(action, esc);
            const img = item.image_url
                ? `<img src="${esc(item.image_url)}" alt="" loading="lazy">`
                : '<div class="builder-collection__placeholder"><i class="fas fa-image"></i></div>';
            const desc = (block.show_description !== false && item.description) ? `<p class="builder-collection__desc">${esc(item.description)}</p>` : '';
            const badge = item.badge ? `<span class="builder-collection__badge">${esc(item.badge)}</span>` : '';
            return `<a href="${nav.href}" class="builder-collection__item builder-nav"${nav.dataExternal}>
                <div class="builder-collection__media" style="height:${imgH}px">${badge}${img}</div>
                <div class="builder-collection__meta">
                    <span class="builder-collection__title">${esc(item.title)}</span>
                    ${desc}
                </div>
            </a>`;
        }).join('');
        const label = block.section_label || block.title || '';
        const moreHref = esc(block.more_action || block.item_action || '#products');
        const head = label
            ? `<div class="builder-section-head"><span class="builder-section-head__label">${esc(label)}</span><a href="${moreHref}" class="builder-section-head__link builder-nav">すべて</a></div>`
            : '';
        return `<section class="builder-block builder-collection-wrap builder-collection-wrap--design-${esc(design)}" style="${baseStyle}">
            ${head}
            <div class="builder-collection ${layoutClass} builder-collection--design-${esc(design)}" style="--collection-gap:${gap}px">${itemsHtml}</div>
        </section>`;
    },
    slider(block, esc, baseStyle) {
        const slides = Array.isArray(block.slides) ? block.slides : [];
        const h = block.slide_height || 200;
        const design = block.design || 'hero';
        if (slides.length === 0) {
            return `<section class="builder-block builder-carousel builder-carousel--empty builder-carousel--design-${esc(design)}" style="--carousel-h:${h}px;${baseStyle}">
                <div class="builder-carousel__empty"><i class="fas fa-images" aria-hidden="true"></i></div>
            </section>`;
        }
        const slideHtml = slides.map((s: any, i: number) => {
            const display = i === 0 ? ' is-active' : '';
            const img = s.image_url ? `<img class="builder-carousel__img" src="${esc(s.image_url)}" alt="" loading="lazy">` : '';
            const caption = s.caption ? `<div class="builder-carousel__caption"><span>${esc(s.caption)}</span></div>` : '';
            return `<div class="builder-carousel__slide${display}" data-slide-index="${i}">${img}${caption}</div>`;
        }).join('');
        const dots = (block.show_dots !== false && slides.length > 1)
            ? `<div class="builder-carousel__dots">${slides.map((_: unknown, i: number) =>
                `<button type="button" class="builder-carousel__dot${i === 0 ? ' is-active' : ''}" data-slide-index="${i}" aria-label="スライド ${i + 1}"></button>`
            ).join('')}</div>`
            : '';
        const label = block.section_label || block.title || '';
        const head = label ? `<div class="builder-section-head"><span class="builder-section-head__label">${esc(label)}</span></div>` : '';
        return `<section class="builder-block builder-carousel-wrap builder-carousel-wrap--design-${esc(design)}" style="${baseStyle}">
            ${head}
            <div class="builder-carousel builder-carousel--design-${esc(design)}" style="--carousel-h:${h}px" data-auto-play="${block.auto_play !== false ? '1' : '0'}" data-slide-count="${slides.length}">
                <div class="builder-carousel__track">${slideHtml}</div>
                ${dots}
            </div>
        </section>`;
    },
    memberSummaryRing(pct) {
        const p = Math.max(0, Math.min(100, pct));
        const dash = `${p * 0.88} 88`;
        return `<div class="ms-ring" aria-hidden="true">
            <svg viewBox="0 0 36 36" class="ms-ring__svg">
                <path class="ms-ring__track" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                <path class="ms-ring__fill" stroke-dasharray="${dash}" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
            </svg>
            <span class="ms-ring__label">${Math.round(p)}%</span>
        </div>`;
    },
    memberSummaryHtml(data, greeting, esc, design) {
        const variant = design || 'rewards';
        const isMember = !!(data && data.is_member === true);
        if (!isMember) {
            return `<div class="member-summary-card member-summary-card--guest member-summary-card--design-${esc(variant)}">
                <div class="ms-guest-copy">
                    <p class="ms-guest-eyebrow">Members</p>
                    <p class="ms-guest-title">${esc(greeting || 'ようこそ')}</p>
                    <p class="ms-guest-sub">会員登録でポイント・クーポン・先行予約が使えます</p>
                </div>
                <a href="#member" class="builder-nav ms-guest-cta"><i class="fas fa-user-plus" aria-hidden="true"></i> 会員になる</a>
            </div>`;
        }
        const name = (data.user && data.user.name) ? String(data.user.name) : '';
        const points = Number(data.points != null ? data.points : (data.member && data.member.points) || 0);
        const pointsLabel = points.toLocaleString('ja-JP');
        const rankLabel = data.current_rank_label || '';
        const showPoints = data.show_points_display !== false;
        const showRank = data.show_rank_progress !== false;
        const nextRankLabel = data.next_rank_label || '';
        const toNext = Number(data.points_to_next_rank != null ? data.points_to_next_rank : 0);
        const hello = greeting ? esc(greeting) : (name ? `こんにちは、${esc(name)} さん` : 'おかえりなさい');

        let pct = 0;
        let progressHtml = '';
        let ringHtml = '';
        if (showRank && nextRankLabel && toNext > 0) {
            const span = points + toNext;
            pct = span > 0 ? Math.max(4, Math.min(100, Math.round((points / span) * 100))) : 0;
            if (variant === 'rewards' || variant === 'glass') ringHtml = premium.memberSummaryRing(pct);
            progressHtml = `<div class="ms-progress">
                <div class="ms-progress-track"><span class="ms-progress-fill" style="width:${pct}%;"></span></div>
                <p class="ms-progress-text"><span class="ms-progress-goal">次の<strong>${esc(nextRankLabel)}</strong>まで</span> あと <strong>${toNext.toLocaleString('ja-JP')}</strong> pt</p>
            </div>`;
        }
        const designClass = `member-summary-card--design-${esc(variant)}`;
        const rankBadge = rankLabel ? `<span class="ms-rank"><i class="fas fa-crown" aria-hidden="true"></i> ${esc(rankLabel)}</span>` : '';
        const pointsBlock = showPoints ? `<div class="ms-points"><span class="ms-points-value">${pointsLabel}</span><span class="ms-points-unit">pt</span></div>` : '<div></div>';
        const cardLink = '<a href="#member" class="builder-nav ms-card-link" aria-label="会員証を表示"><i class="fas fa-qrcode" aria-hidden="true"></i></a>';
        if (variant === 'wallet') return `<div class="member-summary-card member-summary-card--member ${designClass}"><div class="ms-wallet-row"><div class="ms-greeting"><p class="ms-hello">${hello}</p>${rankBadge}</div><div class="ms-wallet-points">${pointsBlock}</div>${cardLink}</div>${progressHtml}</div>`;
        if (variant === 'compact') return `<div class="member-summary-card member-summary-card--member ${designClass}"><div class="ms-compact-row">${rankBadge}<p class="ms-hello">${hello}</p>${showPoints ? `<span class="ms-compact-pt">${pointsLabel} pt</span>` : ''}</div></div>`;
        if (variant === 'emblem') return `<div class="member-summary-card member-summary-card--member ${designClass}"><div class="ms-emblem"><i class="fas fa-crown ms-emblem-icon" aria-hidden="true"></i><p class="ms-hello">${hello}</p>${rankBadge}${showPoints ? `<div class="ms-points ms-points--center">${pointsBlock}</div>` : ''}</div>${progressHtml}</div>`;
        if (variant === 'minimal') return `<div class="member-summary-card member-summary-card--member ${designClass}"><div class="ms-minimal"><p class="ms-hello">${hello}</p>${showPoints ? `<p class="ms-minimal-pt">${pointsLabel} <span>pt</span></p>` : ''}${rankBadge}</div></div>`;
        if (variant === 'split') return `<div class="member-summary-card member-summary-card--member ${designClass}"><div class="ms-split"><div class="ms-split-left"><p class="ms-hello">${hello}</p>${rankBadge}</div><div class="ms-split-right">${showPoints ? pointsBlock : ''}${cardLink}</div></div>${progressHtml}</div>`;
        if (variant === 'glass') return `<div class="member-summary-card member-summary-card--member ${designClass}"><div class="ms-top"><div class="ms-greeting"><p class="ms-hello">${hello}</p>${rankBadge}</div></div><div class="ms-points-row">${pointsBlock}</div>${progressHtml}</div>`;
        if (variant === 'tier') {
            const tierLabel = rankLabel || 'Member';
            return `<div class="member-summary-card member-summary-card--member ${designClass}">
                <div class="ms-tier">
                    <div class="ms-tier__badge"><i class="fas fa-gem" aria-hidden="true"></i><span>${esc(tierLabel)}</span></div>
                    <p class="ms-hello">${hello}</p>
                    ${showPoints ? `<div class="ms-points ms-points--center">${pointsBlock}</div>` : ''}
                </div>
                ${progressHtml}
            </div>`;
        }
        return `<div class="member-summary-card member-summary-card--member ${designClass}"><div class="ms-top"><div class="ms-greeting"><p class="ms-eyebrow">Rewards</p><p class="ms-hello">${hello}</p>${rankBadge}</div>${cardLink}</div><div class="ms-points-row">${pointsBlock}${ringHtml}</div>${progressHtml}</div>`;
    },
};

getLiffLayoutBlocks().premium = premium;
