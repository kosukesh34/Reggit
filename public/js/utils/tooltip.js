class TooltipManager {
    static init() {
        document.addEventListener('mouseover', (e) => {
            const element = e.target.closest('[data-tooltip]');
            if (element && !element.querySelector('.tooltip')) {
                this.showTooltip(element, element.dataset.tooltip);
            }
        });

        document.addEventListener('mouseout', (e) => {
            const element = e.target.closest('[data-tooltip]');
            if (element) {
                this.hideTooltip(element);
            }
        });
    }

    static showTooltip(element, text) {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = text;
        document.body.appendChild(tooltip);

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
        const tooltip = document.querySelector('.tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    TooltipManager.init();
});

