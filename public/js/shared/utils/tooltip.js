class TooltipManager {
    static init() {
        document.addEventListener('mouseover', (e) => {
            const element = e.target.closest('[data-tooltip]');
            if (element) {
                this.showTooltip(element, element.dataset.tooltip);
            }
        });

        document.addEventListener('mouseout', (e) => {
            const element = e.target.closest('[data-tooltip]');
            if (element) {
                this.hideTooltip(element);
            }
        });

        document.addEventListener('click', () => this.hideTooltip());
        document.addEventListener('touchstart', () => this.hideTooltip(), { passive: true });
        window.addEventListener('scroll', () => this.hideTooltip(), { passive: true });
        window.addEventListener('resize', () => this.hideTooltip());
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) this.hideTooltip();
        });
    }

    static showTooltip(element, text) {
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
        let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);

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

    static hideTooltip(element) {
        if (element && this.activeElement && element !== this.activeElement) return;
        if (this.activeTooltip) this.activeTooltip.remove();
        this.activeTooltip = null;
        this.activeElement = null;
        document.querySelectorAll('.tooltip').forEach((tip) => tip.remove());
    }
}

TooltipManager.activeTooltip = null;
TooltipManager.activeElement = null;

document.addEventListener('DOMContentLoaded', () => {
    TooltipManager.init();
});
