import { field, setText } from './dom';
import type { AudiencePreviewMember } from './types';

/** 配信対象者の一覧（表）と、行を押したときの会員詳細モーダル。 */

export function renderAudienceRows(
    target: HTMLTableSectionElement | null,
    members: AudiencePreviewMember[],
    onOpen: (member: AudiencePreviewMember) => void,
): void {
    if (!target) return;
    target.replaceChildren();
    if (members.length === 0) {
        const row = document.createElement('tr');
        const cell = document.createElement('td');
        cell.colSpan = 6;
        cell.className = 'ad-audience-empty';
        cell.textContent = '現在の条件に該当する会員はいません';
        row.appendChild(cell);
        target.appendChild(row);
        return;
    }

    members.forEach((member) => {
        const row = document.createElement('tr');
        row.className = 'ad-audience-member-row';
        row.tabIndex = 0;
        row.setAttribute('role', 'button');
        row.setAttribute('aria-label', `${member.name}の会員情報を表示`);

        const nameCell = document.createElement('td');
        const name = document.createElement('strong');
        name.textContent = member.name;
        const hint = document.createElement('small');
        hint.textContent = 'タップして詳細';
        nameCell.append(name, hint);

        const tagsCell = document.createElement('td');
        const tags = document.createElement('div');
        tags.className = 'ad-audience-tags';
        if (member.tags.length === 0) {
            tags.textContent = '—';
        } else {
            member.tags.forEach((memberTag) => {
                const tag = document.createElement('span');
                tag.style.setProperty('--ad-member-tag-color', memberTag.color);
                tag.textContent = memberTag.name;
                tags.appendChild(tag);
            });
        }
        tagsCell.appendChild(tags);

        const numberCell = document.createElement('td');
        numberCell.textContent = (member.display_identifier ?? member.member_number);
        const sendableCell = document.createElement('td');
        const sendable = document.createElement('span');
        sendable.className = `ad-sendability ${member.notification_sendable ? 'is-sendable' : 'is-blocked'}`;
        sendable.innerHTML = member.notification_sendable
            ? '<i class="fas fa-check" aria-hidden="true"></i> 通知可'
            : '<i class="fas fa-minus" aria-hidden="true"></i> 通知なし';
        sendableCell.appendChild(sendable);
        const channelCell = document.createElement('td');
        channelCell.textContent = member.channel;
        const rankCell = document.createElement('td');
        rankCell.textContent = member.rank;
        row.append(nameCell, tagsCell, numberCell, sendableCell, channelCell, rankCell);
        row.addEventListener('click', () => onOpen(member));
        row.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                onOpen(member);
            }
        });
        target.appendChild(row);
    });
}

function renderMemberDetailTags(member: AudiencePreviewMember): void {
    const container = field<HTMLElement>('audienceMemberDetailTags');
    if (!container) return;
    container.replaceChildren();
    if (member.tags.length === 0) {
        const empty = document.createElement('span');
        empty.className = 'is-empty';
        empty.textContent = 'タグなし';
        container.appendChild(empty);
        return;
    }
    member.tags.forEach((memberTag) => {
        const tag = document.createElement('span');
        tag.style.setProperty('--ad-member-tag-color', memberTag.color);
        tag.textContent = memberTag.name;
        container.appendChild(tag);
    });
}

export function createMemberDetail(modal: HTMLElement | null): {
    open: (member: AudiencePreviewMember) => void;
    close: () => void;
    isOpen: () => boolean;
} {
    const open = (member: AudiencePreviewMember): void => {
        if (!modal) return;
        setText('audienceMemberDetailAvatar', member.name.slice(0, 1) || '—');
        setText('audienceMemberDetailTitle', member.name);
        setText('audienceMemberDetailNumber', (member.display_identifier ?? member.member_number));
        setText('audienceMemberDetailSendable', member.notification_sendable ? '今回の通知を送信できます' : '今回は通知を送信しません');
        setText('audienceMemberDetailReason', member.notification_reason);
        setText('audienceMemberDetailPreference', member.notification_preference_label);
        setText('audienceMemberDetailChannel', member.channel);
        setText('audienceMemberDetailRank', member.rank);
        setText('audienceMemberDetailPoints', `${Number(member.points).toLocaleString('ja-JP')}pt`);
        setText('audienceMemberDetailBirthday', member.birthday);
        setText('audienceMemberDetailSex', member.sex);
        setText('audienceMemberDetailLastVisit', member.last_visited_at);
        setText('audienceMemberDetailPhone', member.phone || '—');
        setText('audienceMemberDetailEmail', member.email || '—');
        renderMemberDetailTags(member);
        field<HTMLElement>('audienceMemberDetailSendableIcon')?.classList.toggle('is-blocked', !member.notification_sendable);
        modal.hidden = false;
        modal.querySelector<HTMLButtonElement>('[data-close-audience-member-detail]')?.focus();
    };
    const close = (): void => {
        if (modal) modal.hidden = true;
    };
    document.querySelectorAll<HTMLElement>('[data-close-audience-member-detail]').forEach((button) => {
        button.addEventListener('click', close);
    });

    return { open, close, isOpen: () => modal?.hidden === false };
}
