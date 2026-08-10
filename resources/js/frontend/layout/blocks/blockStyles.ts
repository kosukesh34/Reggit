import type { CSSProperties } from 'react';
import type { BuilderBlockData } from './types';

/** LiffLayoutManager._renderBlock の baseStyle と同値の CSSProperties */
export function baseStyleObject(block: BuilderBlockData): CSSProperties {
    const br = block.border_radius != null ? Number(block.border_radius) : 10;
    const style: CSSProperties = { borderRadius: `${br}px` };
    if (block.margin_bottom != null && block.margin_bottom !== '' && Number(block.margin_bottom) !== 0) {
        style.marginBottom = `${Number(block.margin_bottom)}px`;
    }
    if (block.shadow) {
        style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
    }
    if (block.padding !== undefined && block.padding !== null) {
        style.padding = `${Number(block.padding)}px`;
    }
    if (block.background_color) {
        style.backgroundColor = String(block.background_color);
    }
    if (block.text_color) {
        style.color = String(block.text_color);
    }
    return style;
}

export function marginBottomStyle(block: BuilderBlockData): CSSProperties {
    if (block.margin_bottom == null || block.margin_bottom === '' || Number(block.margin_bottom) === 0) {
        return {};
    }
    return { marginBottom: `${Number(block.margin_bottom)}px` };
}

export function mergeStyles(...parts: Array<CSSProperties | undefined>): CSSProperties {
    const out: CSSProperties = {};
    for (const part of parts) {
        if (part !== undefined) {
            Object.assign(out, part);
        }
    }
    return out;
}
