import { LiffBuilderManager } from '../frontend/managers/LiffBuilderManager';

type BuilderBlock = Record<string, any>;
type BuilderTemplate = { id: string; name?: string; blocks: BuilderBlock[] };

type LiffBuilderPageConfig = {
    templates?: BuilderTemplate[];
    blocks?: BuilderBlock[];
};

function asInput(el: Element | null): HTMLInputElement | null {
    return el as HTMLInputElement | null;
}

function asSelect(el: Element | null): HTMLSelectElement | null {
    return el as HTMLSelectElement | null;
}

function asTextArea(el: Element | null): HTMLTextAreaElement | null {
    return el as HTMLTextAreaElement | null;
}

export function bootstrapLiffBuilderPage(): void {
    if (document.body.dataset.pageKind !== 'liff-builder') {
        return;
    }

    const cfg = (window.LiffBuilderPageConfig || {}) as LiffBuilderPageConfig;
    const templates = Array.isArray(cfg.templates) ? cfg.templates : [];
    const initialBlocks = Array.isArray(cfg.blocks) ? cfg.blocks : [];
    const blocksContainer = document.getElementById('builderBlocks');
    const preview = document.getElementById('builderPreview');
    if (blocksContainer === null || preview === null) {
        return;
    }

    function addFormField(container: Element, field: Record<string, any> = {}): void {
        const tpl = document.getElementById('formFieldTemplate') as HTMLTemplateElement | null;
        if (tpl === null || tpl.content.firstElementChild === null) {
            return;
        }
        const row = tpl.content.firstElementChild.cloneNode(true) as HTMLElement;
        const label = asInput(row.querySelector('.field-label'));
        const type = asSelect(row.querySelector('.field-type'));
        const placeholder = asInput(row.querySelector('.field-placeholder'));
        const required = asInput(row.querySelector('.field-required'));
        if (label) label.value = field.label || '';
        if (type) type.value = field.type || 'text';
        if (placeholder) placeholder.value = field.placeholder || '';
        if (required) required.checked = !!field.required;
        row.querySelector('.remove-form-field')?.addEventListener('click', () => {
            row.remove();
            renderPreview();
        });
        row.querySelectorAll('input, select').forEach((el) => {
            el.addEventListener('change', renderPreview);
        });
        container.appendChild(row);
    }

    function buildBlockEditor(block: BuilderBlock = {}): HTMLElement {
        const tpl = document.getElementById('blockTemplate') as HTMLTemplateElement | null;
        if (tpl === null || tpl.content.firstElementChild === null) {
            throw new Error('blockTemplate missing');
        }
        const node = tpl.content.firstElementChild.cloneNode(true) as HTMLElement;
        const type = asSelect(node.querySelector('.block-type'));
        const title = asInput(node.querySelector('.block-title'));
        const body = asTextArea(node.querySelector('.block-body'));
        const image = asInput(node.querySelector('.block-image'));
        const buttonLabel = asInput(node.querySelector('.block-button-label'));
        const buttonUrl = asInput(node.querySelector('.block-button-url'));
        const list = asInput(node.querySelector('.block-list'));
        const align = asSelect(node.querySelector('.block-align'));
        const bg = asInput(node.querySelector('.block-bg'));
        const textColor = asInput(node.querySelector('.block-text-color'));
        const formAction = asInput(node.querySelector('.block-form-action'));
        const formButton = asInput(node.querySelector('.block-form-button'));
        const spacerHeight = asInput(node.querySelector('.block-spacer-height'));

        if (type) type.value = block.type || 'text';
        if (title) title.value = block.title || '';
        if (body) body.value = block.body || '';
        if (image) image.value = block.image_url || '';
        if (buttonLabel) buttonLabel.value = block.button_label || '';
        if (buttonUrl) buttonUrl.value = block.button_url || '';
        if (list) {
            list.value = Array.isArray(block.list_items) ? block.list_items.join(', ') : (block.list_items || '');
        }
        if (align) align.value = block.align || 'left';
        if (bg) bg.value = block.background_color || '';
        if (textColor) textColor.value = block.text_color || '';
        if (formAction) formAction.value = block.form_action || '';
        if (formButton) formButton.value = block.form_button_label || '';
        if (spacerHeight) spacerHeight.value = String(block.height || 24);

        const fieldsList = node.querySelector('.form-fields-list');
        if (fieldsList !== null) {
            (block.form_fields || []).forEach((field: Record<string, any>) => addFormField(fieldsList, field));
        }

        node.querySelector('.btn-remove-block')?.addEventListener('click', () => {
            node.remove();
            renderPreview();
        });
        node.querySelectorAll('input, textarea, select').forEach((el) => {
            el.addEventListener('change', renderPreview);
        });
        node.querySelector('.add-form-field')?.addEventListener('click', () => {
            if (fieldsList !== null) {
                addFormField(fieldsList, {});
                renderPreview();
            }
        });

        return node;
    }

    function gatherBlocks(): BuilderBlock[] {
        const blocks: BuilderBlock[] = [];
        blocksContainer.querySelectorAll('.builder-block-editor').forEach((node) => {
            const block: BuilderBlock = {
                type: asSelect(node.querySelector('.block-type'))?.value,
                title: asInput(node.querySelector('.block-title'))?.value,
                body: asTextArea(node.querySelector('.block-body'))?.value,
                image_url: asInput(node.querySelector('.block-image'))?.value,
                button_label: asInput(node.querySelector('.block-button-label'))?.value,
                button_url: asInput(node.querySelector('.block-button-url'))?.value,
                list_items: asInput(node.querySelector('.block-list'))?.value,
                align: asSelect(node.querySelector('.block-align'))?.value,
                background_color: asInput(node.querySelector('.block-bg'))?.value,
                text_color: asInput(node.querySelector('.block-text-color'))?.value,
                form_action: asInput(node.querySelector('.block-form-action'))?.value,
                form_button_label: asInput(node.querySelector('.block-form-button'))?.value,
            };
            if (block.type === 'spacer') {
                block.height = parseInt(asInput(node.querySelector('.block-spacer-height'))?.value || '24', 10);
            }
            if (block.type === 'form') {
                block.form_fields = [];
                node.querySelectorAll('.form-field-row').forEach((row) => {
                    block.form_fields.push({
                        label: asInput(row.querySelector('.field-label'))?.value,
                        type: asSelect(row.querySelector('.field-type'))?.value,
                        placeholder: asInput(row.querySelector('.field-placeholder'))?.value,
                        required: !!asInput(row.querySelector('.field-required'))?.checked,
                    });
                });
            }
            blocks.push(block);
        });
        return blocks;
    }

    function renderPreview(): void {
        const blocks = gatherBlocks();
        preview.innerHTML = '';
        const manager = new LiffBuilderManager();
        manager.render(preview, blocks);
    }

    function rebuildBlocks(blocks: BuilderBlock[]): void {
        blocksContainer.innerHTML = '';
        blocks.forEach((block) => blocksContainer.appendChild(buildBlockEditor(block)));
        renderPreview();
    }

    document.getElementById('addBlockBtn')?.addEventListener('click', () => {
        blocksContainer.appendChild(buildBlockEditor({}));
        renderPreview();
    });

    document.querySelectorAll('[data-template-id]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const id = (btn as HTMLElement).getAttribute('data-template-id');
            const template = templates.find((t) => t.id === id);
            if (template) {
                rebuildBlocks(template.blocks);
            }
        });
    });

    rebuildBlocks(initialBlocks);

    document.getElementById('liffBuilderForm')?.addEventListener('submit', function (this: HTMLFormElement) {
        const blocks = gatherBlocks();
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'blocks_json';
        hidden.value = JSON.stringify(blocks);
        this.appendChild(hidden);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => bootstrapLiffBuilderPage());
} else {
    bootstrapLiffBuilderPage();
}
