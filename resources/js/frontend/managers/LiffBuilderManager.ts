import { AppConstants } from '../../shared/constants';
import { ApiClient } from '../../shared/utils/apiClient';
import { DomHelper } from '../../shared/utils/domHelper';
import { ToastManager } from '../../shared/utils/toast';
import { GalleryManager } from './GalleryManager';

export class LiffBuilderManager {
    [key: string]: any;

    constructor(apiClient: ApiClient | null = null) {
        this.apiClient = apiClient || new ApiClient();
        this.galleryManager = new GalleryManager(this.apiClient);
    }

    getSection() {
        return DomHelper.getElementById(AppConstants.ELEMENT_IDS.HOME_SECTION);
    }

    load() {
        const section = this.getSection();
        if (!section) return;
        const blocks = (typeof window !== 'undefined' && window.__LIFF_BUILDER__) ? window.__LIFF_BUILDER__ : [];
        this.render(section, blocks);
    }

    render(section, blocks) {
        if (!Array.isArray(blocks) || blocks.length === 0) {
            section.innerHTML = '<div class="builder-empty">コンテンツが設定されていません。</div>';
            return;
        }
        const html = blocks.map((block) => this.renderBlock(block)).join('');
        section.innerHTML = `<div class="builder-container">${html}</div>`;
        this.bindFormHandlers(section);
        section.querySelectorAll('[data-gallery]').forEach((el) => {
            if (this.galleryManager && this.galleryManager.loadEmbedded) {
                this.galleryManager.loadEmbedded(el);
            }
        });
    }

    renderBlock(block) {
        const type = block.type || 'text';
        const title = block.title ? `<h3 class="builder-title">${DomHelper.escapeHtml(block.title)}</h3>` : '';
        const body = block.body ? `<p class="builder-body">${DomHelper.escapeHtml(block.body)}</p>` : '';
        const align = block.align || 'left';
        const style = this.buildStyle(block);
        const button = block.button_label
            ? `<a class="builder-btn" href="${DomHelper.escapeHtml(block.button_url || '#')}">${DomHelper.escapeHtml(block.button_label)}</a>`
            : '';
        if (type === 'hero') {
            const image = block.image_url ? `<img class="builder-hero-image" src="${DomHelper.escapeHtml(block.image_url)}" alt="">` : '';
            return `
                <section class="builder-block builder-hero align-${align}" style="${style}">
                    ${image}
                    <div class="builder-hero-content">
                        ${title}
                        ${body}
                        ${button}
                    </div>
                </section>
            `;
        }
        if (type === 'image') {
            const image = block.image_url ? `<img class="builder-image" src="${DomHelper.escapeHtml(block.image_url)}" alt="">` : '';
            return `
                <section class="builder-block builder-image-block align-${align}" style="${style}">
                    ${title}
                    ${image}
                    ${body}
                </section>
            `;
        }
        if (type === 'button') {
            return `
                <section class="builder-block builder-button-block align-${align}" style="${style}">
                    ${title}
                    ${body}
                    ${button}
                </section>
            `;
        }
        if (type === 'list') {
            const items = Array.isArray(block.list_items)
                ? block.list_items
                : (block.list_items ? String(block.list_items).split(',') : []);
            const list = items.length
                ? `<ul class="builder-list">${items.map((item) => `<li>${DomHelper.escapeHtml(item.trim())}</li>`).join('')}</ul>`
                : '';
            return `
                <section class="builder-block builder-list-block align-${align}" style="${style}">
                    ${title}
                    ${body}
                    ${list}
                </section>
            `;
        }
        if (type === 'form') {
            const fields = Array.isArray(block.form_fields) ? block.form_fields : [];
            const formFields = fields.map((field, idx) => {
                const label = DomHelper.escapeHtml(field.label || `項目${idx + 1}`);
                const placeholder = DomHelper.escapeHtml(field.placeholder || '');
                const required = field.required ? 'required' : '';
                const name = `field_${idx}`;
                if (field.type === 'textarea') {
                    return `
                        <label class="builder-form-label">${label}
                            <textarea name="${name}" placeholder="${placeholder}" ${required}></textarea>
                        </label>
                    `;
                }
                const typeAttr = DomHelper.escapeHtml(field.type || 'text');
                return `
                    <label class="builder-form-label">${label}
                        <input type="${typeAttr}" name="${name}" placeholder="${placeholder}" ${required} />
                    </label>
                `;
            }).join('');
            const action = DomHelper.escapeHtml(block.form_action || '');
            return `
                <section class="builder-block builder-form-block align-${align}" style="${style}">
                    ${title}
                    ${body}
                    <form class="builder-form" data-action="${action}">
                        ${formFields}
                        <button type="submit" class="builder-btn">${DomHelper.escapeHtml(block.form_button_label || '送信')}</button>
                    </form>
                </section>
            `;
        }
        if (type === 'gallery') {
            return `
                <section class="builder-block builder-gallery-block" style="${style}">
                    ${title}
                    ${body}
                    <div class="builder-gallery-container" data-gallery></div>
                </section>
            `;
        }
        if (type === 'spacer') {
            const height = block.height || 24;
            return `<div class="builder-spacer" style="height:${height}px;"></div>`;
        }
        return `
            <section class="builder-block builder-text-block align-${align}" style="${style}">
                ${title}
                ${body}
                ${button}
            </section>
        `;
    }

    buildStyle(block) {
        const styles = [];
        if (block.background_color) styles.push(`background:${block.background_color}`);
        if (block.text_color) styles.push(`color:${block.text_color}`);
        return styles.join(';');
    }

    bindFormHandlers(section) {
        section.querySelectorAll('.builder-form').forEach((form) => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const action = form.getAttribute('data-action');
                if (!action) {
                    ToastManager.info('送信先が未設定です');
                    return;
                }
                const data = {};
                new FormData(form).forEach((value, key) => {
                    data[key] = value;
                });
                try {
                    const res = await fetch(action, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data),
                    });
                    if (!res.ok) {
                        throw new Error('送信に失敗しました');
                    }
                    ToastManager.success('送信しました');
                    form.reset();
                } catch (err) {
                    ToastManager.error(err.message || '送信に失敗しました');
                }
            });
        });

        section.querySelectorAll('.builder-btn').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                const href = btn.getAttribute('href');
                if (href && href.startsWith('#')) {
                    e.preventDefault();
                    const tab = href.replace('#', '');
                    if (window.app && tab) {
                        window.app.switchTab(tab);
                    }
                }
            });
        });
    }
}


window.LiffBuilderManager = LiffBuilderManager;
