window.LiffDesignerModules = window.LiffDesignerModules || {};

window.LiffDesignerModules.dataPreview = {
    buildData(el, b) {
        const src = this.DATA_SOURCES.find(s => s.value === b.source);
        const label = src ? src.label : b.source;
        const frameUrl = this.getDataPreviewFrameUrl ? this.getDataPreviewFrameUrl(b.source) : '';
        el.innerHTML +=
            '<div class="ld-block__body ld-block--data-body">' +
                '<div class="ld-block--data-title"><i class="fas fa-database"></i>' + this.esc(label) + '（自動表示）</div>' +
                '<div class="ld-data-sample">' + this.renderDataSourcePreview(b.source) + '</div>' +
            '</div>';
        if (frameUrl) {
            el.querySelector('.ld-data-sample').innerHTML =
                '<div class="ld-data-live">' +
                    '<iframe class="ld-data-live__frame" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" src="' + this.esc(frameUrl) + '" title="' + this.esc(label) + ' プレビュー"></iframe>' +
                '</div>';
        }
    },

    renderDataSourcePreview(source) {
        return '<div class="ld-data-sample__empty">ライブプレビューを読み込み中...</div>';
    },
};
