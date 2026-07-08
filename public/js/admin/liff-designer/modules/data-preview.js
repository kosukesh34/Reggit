window.LiffDesignerModules = window.LiffDesignerModules || {};

/**
 * データブロックのプレビュー。
 *
 * 以前は「（自動表示）」ラベル＋ライブiframe を表示していたが、
 * 管理者が「実際にどう表示されるか」を一目で把握できるよう、
 * 各データソースの “本来表示される UI” をモックでインライン描画する。
 */
window.LiffDesignerModules.dataPreview = {
    buildData(el, b) {
        const src = this.DATA_SOURCES.find(s => s.value === b.source);
        const label = src ? src.label : b.source;
        el.innerHTML +=
            '<div class="ld-block__body ld-block--data-body">' +
                '<div class="ld-data-head">' +
                    '<span class="ld-data-head__label"><i class="fas fa-bolt"></i>' + this.esc(label) + '</span>' +
                    '<span class="ld-data-head__badge">ライブデータ</span>' +
                '</div>' +
                '<div class="ld-data-sample">' + this.renderDataSourcePreview(b.source) + '</div>' +
            '</div>';
    },

    renderDataSourcePreview(source) {
        const M = window.LiffDesignerModules.dataPreview;
        const fn = M.MOCKS[source] || M.MOCKS._default;
        return '<div class="ld-mock">' + fn() + '</div>';
    },

    MOCKS: {
        member_card() {
            return '' +
                '<div class="ld-mock-card">' +
                    '<div class="ld-mock-card__head">' +
                        '<div><div class="ld-mock-card__shop">reggit メンバーズ</div><div class="ld-mock-card__name">デモ 太郎</div></div>' +
                        '<span class="ld-mock-card__rank">シルバー</span>' +
                    '</div>' +
                    '<div class="ld-mock-card__points"><span>1,280</span> pt</div>' +
                    '<div class="ld-mock-card__barcode">' +
                        Array.from({ length: 34 }).map((_, i) => '<i style="width:' + ((i % 4) + 1) + 'px"></i>').join('') +
                    '</div>' +
                    '<div class="ld-mock-card__no">No. M999001</div>' +
                    '<div class="ld-mock-card__wallet-btns">' +
                        '<span class="ld-mock-wallet-btn ld-mock-wallet-btn--apple"><i class="fab fa-apple"></i> Apple Walletに追加</span>' +
                        '<span class="ld-mock-wallet-btn ld-mock-wallet-btn--google"><i class="fab fa-google"></i> Google Walletに追加</span>' +
                    '</div>' +
                '</div>';
        },
        coupons() {
            const item = (icon, title, meta, available) =>
                '<div class="ld-mock-coupon' + (available ? ' is-on' : '') + '">' +
                    '<div class="ld-mock-coupon__icon"><i class="fas ' + icon + '"></i></div>' +
                    '<div class="ld-mock-coupon__body"><div class="ld-mock-coupon__title">' + title + '</div><div class="ld-mock-coupon__meta">' + meta + '</div></div>' +
                    '<span class="ld-mock-coupon__status">' + (available ? '利用可' : '使用済') + '</span>' +
                '</div>';
            return item('fa-mug-hot', 'ドリンク1杯無料', '〜2027/6/30', true) +
                   item('fa-percent', '会計から10%OFF', '〜2027/6/30', true) +
                   item('fa-gift', 'デザートプレゼント', '使用済み', false);
        },
        stamps() {
            let dots = '';
            for (let i = 0; i < 10; i++) {
                dots += '<span class="ld-mock-stamp' + (i < 7 ? ' is-on' : '') + '">' + (i < 7 ? '<i class="fas fa-star"></i>' : (i + 1)) + '</span>';
            }
            return '' +
                '<div class="ld-mock-stampcard">' +
                    '<div class="ld-mock-stampcard__head"><span>来店スタンプ</span><strong>7 / 10</strong></div>' +
                    '<div class="ld-mock-stampcard__grid">' + dots + '</div>' +
                    '<div class="ld-mock-stampcard__foot"><i class="fas fa-gift"></i> あと3個で特典ゲット</div>' +
                '</div>';
        },
        products() {
            const card = (img, name, price) =>
                '<div class="ld-mock-prod">' +
                    '<div class="ld-mock-prod__img" style="background-image:url(\'' + img + '\')"></div>' +
                    '<div class="ld-mock-prod__name">' + name + '</div>' +
                    '<div class="ld-mock-prod__row"><span class="ld-mock-prod__price">¥' + price + '</span><span class="ld-mock-prod__add"><i class="fas fa-plus"></i></span></div>' +
                '</div>';
            return '<div class="ld-mock-grid2">' +
                card('https://images.unsplash.com/photo-1550547660-d9450f859349?w=300', 'シグネチャーバーガー', '980') +
                card('https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=300', '季節のサラダ', '680') +
            '</div>';
        },
        orders() {
            const row = (no, status, statusCls, items, total) =>
                '<div class="ld-mock-order">' +
                    '<div class="ld-mock-order__top"><span class="ld-mock-order__no">#' + no + '</span><span class="ld-mock-order__status ' + statusCls + '">' + status + '</span></div>' +
                    '<div class="ld-mock-order__items">' + items + '</div>' +
                    '<div class="ld-mock-order__total">合計 <strong>¥' + total + '</strong></div>' +
                '</div>';
            return row('1042', '受取準備中', 'is-progress', 'シグネチャーバーガー × 1 ほか', '1,660') +
                   row('1031', '受取完了', 'is-done', '季節のサラダ × 2', '1,360');
        },
        queue() {
            return '' +
                '<div class="ld-mock-queue">' +
                    '<div class="ld-mock-queue__label">あなたの番号</div>' +
                    '<div class="ld-mock-queue__num">A-12</div>' +
                    '<div class="ld-mock-queue__wait"><i class="fas fa-users"></i> 前に 3 組 / 約 15 分</div>' +
                '</div>';
        },
        reservations() {
            const row = (date, time, party, status, cls) =>
                '<div class="ld-mock-resv">' +
                    '<div class="ld-mock-resv__date"><span class="ld-mock-resv__d">' + date + '</span><span class="ld-mock-resv__t">' + time + '</span></div>' +
                    '<div class="ld-mock-resv__body"><div>' + party + '</div><span class="ld-mock-resv__status ' + cls + '">' + status + '</span></div>' +
                '</div>';
            return row('6/20', '18:30', '2名 / 店内', '確定', 'is-ok') +
                   row('6/27', '12:00', '4名 / テラス', '確認中', 'is-wait');
        },
        gallery() {
            const imgs = [
                'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=300',
                'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=300',
                'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=300',
            ];
            return '<div class="ld-mock-gallery">' +
                imgs.map(u => '<div class="ld-mock-gallery__img" style="background-image:url(\'' + u + '\')"></div>').join('') +
            '</div>';
        },
        announcements() {
            const row = (date, title) =>
                '<div class="ld-mock-ann"><span class="ld-mock-ann__date">' + date + '</span><span class="ld-mock-ann__title">' + title + '</span></div>';
            return row('6/13', '夏季限定メニューが登場しました') +
                   row('6/01', '営業時間変更のお知らせ');
        },
        _default() {
            return '<div class="ld-data-sample__empty">このデータは実際の利用者画面に表示されます</div>';
        },
    },
};
