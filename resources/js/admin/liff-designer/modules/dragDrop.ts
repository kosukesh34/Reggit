import { ensureLiffDesignerModules } from '../ensureModules';

type BlockTypeMeta = { type: string; label: string; icon: string };
type DesignerBlock = { id: string; type: string };

type LiffDesignerDragHost = {
    drag: {
        active: boolean;
        source: 'palette' | 'reorder' | null;
        blockType: string | null;
        blockId: string | null;
        ghost: HTMLElement | null;
        dropIndex: number;
        startX: number;
        startY: number;
        scrollTimer: number | null;
        pendingStart: boolean;
        sourceElement: HTMLElement | null;
    };
    els: {
        phoneContent?: HTMLElement | null;
        phoneBody?: HTMLElement | null;
    };
    BLOCK_TYPES: BlockTypeMeta[];
    handlePointerDown: (e: PointerEvent) => void;
    handlePointerMove: (e: PointerEvent) => void;
    handlePointerUp: () => void;
    createGhost: (e: PointerEvent) => void;
    updateDropTarget: (e: PointerEvent) => void;
    autoScroll: (e: PointerEvent) => void;
    clearDropIndicators: () => void;
    resetDrag: () => void;
    addBlock: (type: string | null, index: number) => void;
    getBlocks: () => DesignerBlock[];
    findBlock: (id: string | null) => DesignerBlock | null;
    pushHistory: () => void;
    renderPhoneContent: () => void;
    renderScreenList: () => void;
    esc: (value: string) => string;
};

export const dragDrop = {
    bindDragSystem(this: LiffDesignerDragHost): void {
        document.addEventListener('pointerdown', this.handlePointerDown.bind(this) as EventListener);
        document.addEventListener('pointermove', this.handlePointerMove.bind(this) as EventListener);
        document.addEventListener('pointerup', this.handlePointerUp.bind(this) as EventListener);
        document.addEventListener('pointercancel', this.handlePointerUp.bind(this) as EventListener);
    },

    handlePointerDown(this: LiffDesignerDragHost, e: PointerEvent): void {
        const target = e.target as HTMLElement | null;
        if (target === null) {
            return;
        }
        const pi = target.closest('.ld-palette-item') as HTMLElement | null;
        const bh = target.closest('.ld-block__handle') as HTMLElement | null;
        if (pi === null && bh === null) {
            return;
        }
        this.drag.startX = e.clientX;
        this.drag.startY = e.clientY;
        if (pi !== null) {
            this.drag.source = 'palette';
            this.drag.blockType = pi.dataset.blockType || null;
            this.drag.sourceElement = pi;
        } else if (bh !== null) {
            const bl = bh.closest('.ld-block') as HTMLElement | null;
            if (bl === null) {
                return;
            }
            this.drag.source = 'reorder';
            this.drag.blockId = bl.dataset.id || null;
            this.drag.sourceElement = bl;
        }
        this.drag.pendingStart = true;
    },

    handlePointerMove(this: LiffDesignerDragHost, e: PointerEvent): void {
        if (!this.drag.pendingStart && !this.drag.active) {
            return;
        }
        if (this.drag.pendingStart) {
            if (Math.abs(e.clientX - this.drag.startX) < 5 && Math.abs(e.clientY - this.drag.startY) < 5) {
                return;
            }
            e.preventDefault();
            this.drag.pendingStart = false;
            this.drag.active = true;
            document.body.classList.add('ld-is-dragging');
            this.createGhost(e);
            if (this.drag.source === 'reorder' && this.drag.sourceElement !== null) {
                this.drag.sourceElement.classList.add('ld-block--dragging');
            }
            if (this.els.phoneContent) {
                this.els.phoneContent.classList.add('ld-drop-zone-active');
            }
        }
        if (!this.drag.active) {
            return;
        }
        if (this.drag.ghost !== null) {
            this.drag.ghost.style.left = `${e.clientX}px`;
            this.drag.ghost.style.top = `${e.clientY}px`;
        }
        this.updateDropTarget(e);
        this.autoScroll(e);
    },

    handlePointerUp(this: LiffDesignerDragHost): void {
        if (this.drag.pendingStart) {
            this.drag.pendingStart = false;
            this.resetDrag();
            return;
        }
        if (!this.drag.active) {
            return;
        }
        const di = this.drag.dropIndex;
        if (this.drag.source === 'palette' && di >= 0) {
            this.addBlock(this.drag.blockType, di);
        } else if (this.drag.source === 'reorder' && di >= 0) {
            const blocks = this.getBlocks();
            const fi = blocks.findIndex((b) => b.id === this.drag.blockId);
            if (fi >= 0) {
                const [m] = blocks.splice(fi, 1);
                blocks.splice(di > fi ? di - 1 : di, 0, m);
                this.pushHistory();
                this.renderPhoneContent();
                this.renderScreenList();
            }
        }
        this.resetDrag();
    },

    createGhost(this: LiffDesignerDragHost, e: PointerEvent): void {
        const g = document.createElement('div');
        g.className = 'ld-drag-ghost';
        const bt = this.drag.source === 'palette'
            ? this.BLOCK_TYPES.find((b) => b.type === this.drag.blockType)
            : (() => {
                const bl = this.findBlock(this.drag.blockId);
                return bl ? this.BLOCK_TYPES.find((b) => b.type === bl.type) : null;
            })();
        g.innerHTML = `<i class="fas ${bt ? bt.icon : 'fa-cube'}"></i> ${this.esc(bt ? bt.label : '')}`;
        g.style.left = `${e.clientX}px`;
        g.style.top = `${e.clientY}px`;
        document.body.appendChild(g);
        this.drag.ghost = g;
        requestAnimationFrame(() => g.classList.add('ld-drag-ghost--visible'));
    },

    updateDropTarget(this: LiffDesignerDragHost, e: PointerEvent): void {
        this.clearDropIndicators();
        const body = this.els.phoneBody;
        const content = this.els.phoneContent;
        if (!body || !content) {
            this.drag.dropIndex = -1;
            return;
        }
        const r = body.getBoundingClientRect();
        if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) {
            this.drag.dropIndex = -1;
            return;
        }
        const blocks = content.querySelectorAll('.ld-block');
        if (blocks.length === 0) {
            this.drag.dropIndex = 0;
            content.classList.add('ld-drop-zone-highlight');
            return;
        }
        let ci = blocks.length;
        let cd = Infinity;
        blocks.forEach((b, i) => {
            const br = b.getBoundingClientRect();
            const d = Math.abs(e.clientY - (br.top + br.height / 2));
            if (d < cd) {
                cd = d;
                ci = e.clientY < br.top + br.height / 2 ? i : i + 1;
            }
        });
        if (this.drag.source === 'reorder') {
            const cur = this.getBlocks().findIndex((b) => b.id === this.drag.blockId);
            if (ci === cur || ci === cur + 1) {
                this.drag.dropIndex = -1;
                return;
            }
        }
        this.drag.dropIndex = ci;
        const ind = document.createElement('div');
        ind.className = 'ld-drop-indicator';
        ind.innerHTML = '<div class="ld-drop-indicator__dot"></div><div class="ld-drop-indicator__line"></div><div class="ld-drop-indicator__dot"></div>';
        if (ci < blocks.length) {
            blocks[ci].parentNode?.insertBefore(ind, blocks[ci]);
        } else {
            content.appendChild(ind);
        }
        requestAnimationFrame(() => ind.classList.add('ld-drop-indicator--visible'));
    },

    clearDropIndicators(this: LiffDesignerDragHost): void {
        document.querySelectorAll('.ld-drop-indicator').forEach((el) => el.remove());
        if (this.els.phoneContent) {
            this.els.phoneContent.classList.remove('ld-drop-zone-highlight');
        }
    },

    autoScroll(this: LiffDesignerDragHost, e: PointerEvent): void {
        const b = this.els.phoneBody;
        if (!b) {
            return;
        }
        const r = b.getBoundingClientRect();
        if (this.drag.scrollTimer !== null) {
            cancelAnimationFrame(this.drag.scrollTimer);
            this.drag.scrollTimer = null;
        }
        const doUp = (): void => {
            b.scrollTop -= 8;
            if (this.drag.active) {
                this.drag.scrollTimer = requestAnimationFrame(doUp);
            }
        };
        const doDown = (): void => {
            b.scrollTop += 8;
            if (this.drag.active) {
                this.drag.scrollTimer = requestAnimationFrame(doDown);
            }
        };
        if (e.clientY < r.top + 40 && e.clientY > r.top) {
            this.drag.scrollTimer = requestAnimationFrame(doUp);
        } else if (e.clientY > r.bottom - 40 && e.clientY < r.bottom) {
            this.drag.scrollTimer = requestAnimationFrame(doDown);
        }
    },

    resetDrag(this: LiffDesignerDragHost): void {
        if (this.drag.ghost !== null) {
            this.drag.ghost.remove();
        }
        if (this.drag.scrollTimer !== null) {
            cancelAnimationFrame(this.drag.scrollTimer);
        }
        if (this.drag.sourceElement !== null) {
            this.drag.sourceElement.classList.remove('ld-block--dragging');
        }
        this.clearDropIndicators();
        document.body.classList.remove('ld-is-dragging');
        if (this.els.phoneContent) {
            this.els.phoneContent.classList.remove('ld-drop-zone-active');
        }
        this.drag = {
            active: false,
            source: null,
            blockType: null,
            blockId: null,
            ghost: null,
            dropIndex: -1,
            startX: 0,
            startY: 0,
            scrollTimer: null,
            pendingStart: false,
            sourceElement: null,
        };
    },
};

ensureLiffDesignerModules().dragDrop = dragDrop;
