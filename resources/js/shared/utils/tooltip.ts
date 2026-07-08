export class TooltipManager {
    static activeTooltip: HTMLElement | null = null;
    static activeElement: Element | null = null;

    static init(): void {
        document.addEventListener('mouseover', (e) => {
            const target = e.target as Element | null;
            const element = target?.closest?.('[data-tooltip]');
            if (element instanceof HTMLElement) {
                this.showTooltip(element, element.dataset.tooltip || '');
            }
        });

        document.addEventListener('mouseout', (e) => {
            const target = e.target as Element | null;
            const element = target?.closest?.('[data-tooltip]');
            if (element) {
                this.hideTooltip(element);
            }
        });

        document.addEventListener('click', () => this.hideTooltip());
        document.addEventListener('touchstart', () => this.hideTooltip(), { passive: true });
        window.addEventListener('scroll', () => this.hideTooltip(), { passive: true });
        window.addEventListener('resize', () => this.hideTooltip());
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.hideTooltip();
            }
        });
    }

    static showTooltip(element: HTMLElement, text: string): void {
        this.hideTooltip();
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = text;
        document.body.appendChild(tooltip);
        this.activeTooltip = tooltip;
        this.activeElement = element;

        const rect = element.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();

        let top = rect.top - tooltipRect.height - 8;
        let left = rect.left + rect.width / 2 - tooltipRect.width / 2;

        if (top < 0) {
            top = rect.bottom + 8;
        }

        if (left < 0) {
            left = 8;
        } else if (left + tooltipRect.width > window.innerWidth) {
            left = window.innerWidth - tooltipRect.width - 8;
        }

        tooltip.style.top = `${top}px`;
        tooltip.style.left = `${left}px`;
        tooltip.classList.add('show');
    }

    static hideTooltip(element?: Element | null): void {
        if (element && this.activeElement && element !== this.activeElement) {
            return;
        }
        if (this.activeTooltip) {
            this.activeTooltip.remove();
        }
        this.activeTooltip = null;
        this.activeElement = null;
        document.querySelectorAll('.tooltip').forEach((tip) => tip.remove());
    }
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            TooltipManager.init();
        });
    } else {
        TooltipManager.init();
    }
}
