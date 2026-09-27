export type HtmlEscaper = (value: string) => string;

export type GuestRegisterCopy = {
    title: string;
    subtitle: string;
    buttonLabel: string;
    buttonClass: string;
};

export function renderLoginOptionalTemplate(
    escapeHtml: HtmlEscaper,
    title = '会員証',
    message = 'ログインするとご利用いただけます。',
): string {
    return `
        <div class="member-card-container">
            <div class="member-card member-card--line-only">
                <div class="member-card-header">
                    <h2 class="member-card-title"><i class="fas fa-id-card"></i> ${escapeHtml(title)}</h2>
                </div>
                <div class="member-card-line-only-body">
                    <p class="member-card-line-only-message">${escapeHtml(message)}</p>
                </div>
            </div>
        </div>
    `;
}

export function renderGuestNoCardTemplate(): string {
    return `
        <div class="member-card-container">
            <div class="member-card member-card--line-only">
                <div class="member-card-line-only-body">
                    <p class="member-card-line-only-kicker">MEMBER PASS</p>
                    <p class="member-card-line-only-message">会員パスはまだありません</p>
                    <p class="member-card-line-only-sub">発行すると、店舗でポイントやクーポンをご利用いただけます。</p>
                    <button type="button" class="btn btn-primary mt-2 member-register-trigger" data-action="member-open-register" onclick="typeof window.app !== 'undefined' && window.app.memberManager && window.app.memberManager.navigateToRegisterPage()">
                        会員パスを発行
                    </button>
                </div>
            </div>
        </div>
    `;
}

export function renderGuestRegisterTemplate(escapeHtml: HtmlEscaper, copy: GuestRegisterCopy): string {
    return `
        <div class="member-card-container">
            <div class="member-card member-card--line-only">
                <div class="member-card-line-only-body">
                    <p class="member-card-line-only-kicker">MEMBER PASS</p>
                    <p class="member-card-line-only-message">${escapeHtml(copy.title)}</p>
                    <p class="member-card-line-only-sub">${escapeHtml(copy.subtitle)}</p>
                    <button type="button" class="btn ${escapeHtml(copy.buttonClass)} mt-2 member-register-trigger" data-action="member-open-register" onclick="typeof window.app !== 'undefined' && window.app.memberManager && window.app.memberManager.navigateToRegisterPage()">
                        ${escapeHtml(copy.buttonLabel)}
                    </button>
                </div>
            </div>
        </div>
    `;
}

export function renderMemberErrorTemplate(escapeHtml: HtmlEscaper, error: unknown): string {
    const message = error instanceof Error && error.message ? error.message : 'エラーが発生しました';
    return `
        <div class="member-card-container">
            <div class="member-card member-card--error">
                <div class="member-card-header">
                    <h2 class="member-card-title"><i class="fas fa-id-card"></i> 会員証</h2>
                </div>
                <div class="member-card-error-body">
                    <p class="member-card-error-icon"><i class="fas fa-exclamation-circle"></i></p>
                    <p class="member-card-error-message">${escapeHtml(message)}</p>
                    <p class="member-card-error-hint">しばらくしてから再度お試しください。</p>
                    <button type="button" class="register-member-btn" onclick="window.app.memberManager.load()">
                        <i class="fas fa-redo"></i> 再読み込み
                    </button>
                </div>
            </div>
        </div>
    `;
}

export function renderMemberSkeleton(): string {
    return `
        <div class="member-card-container">
            <div class="member-card">
                <div class="member-card-header">
                    <div class="skeleton-line skeleton-header"></div>
                </div>
                <div class="barcode-container">
                    <div class="skeleton-barcode">
                        <div class="skeleton-shimmer"></div>
                    </div>
                </div>
                <div class="points-display">
                    <div class="skeleton-line skeleton-points-value"></div>
                    <div class="skeleton-line skeleton-points-label"></div>
                </div>
            </div>
        </div>
    `;
}
