import { fetchJson, field } from './dom';
import type { MemberSearchResult } from './types';

/** 「会員を選ぶ」配信の会員選択: 検索・CSV 取り込み・選択中の表示と送信用 hidden。 */

type MemberPickerOptions = {
    membersUrl: string;
    selectedMembers: Map<number, string>;
    isPersonal: () => boolean;
    /** 選択した会員が変わったとき（対象者一覧の取り直し） */
    onSelectionChange: () => void;
    /** 選択中の表示を描き直したとき（送信ボタンの文言） */
    onRendered: () => void;
};

function memberLabel(member: MemberSearchResult): { name: string; number: string } {
    return {
        name: member.name?.trim() || '名前未登録',
        number: (member.display_identifier ?? member.member_number)?.trim() || `会員ID: ${member.id}`,
    };
}

export function createMemberPicker(options: MemberPickerOptions): { render: () => void } {
    const { selectedMembers } = options;
    const searchInput = field<HTMLInputElement>('memberSearch');
    const searchResults = field<HTMLElement>('memberSearchResults');
    const selectedMembersElement = field<HTMLElement>('selectedMembers');
    const selectedMembersHidden = field<HTMLElement>('targetMemberIdsHidden');
    const memberCsv = field<HTMLInputElement>('memberCsv');
    const memberCsvMessage = field<HTMLElement>('memberCsvMessage');
    let searchTimer: number | undefined;

    function render(): void {
        if (!selectedMembersElement || !selectedMembersHidden) return;
        selectedMembersElement.replaceChildren();
        selectedMembersHidden.replaceChildren();

        if (selectedMembers.size === 0) {
            const empty = document.createElement('p');
            empty.className = 'ad-selected-members-empty';
            empty.textContent = '配信する会員はまだ選択されていません';
            selectedMembersElement.appendChild(empty);
        }

        selectedMembers.forEach((label, id) => {
            const tag = document.createElement('span');
            tag.className = 'ad-member-tag';
            tag.append(document.createTextNode(label));
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.setAttribute('aria-label', `${label}を選択解除`);
            remove.textContent = '×';
            remove.addEventListener('click', () => {
                selectedMembers.delete(id);
                render();
                options.onSelectionChange();
            });
            tag.appendChild(remove);
            selectedMembersElement.appendChild(tag);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'target_member_ids[]';
            hidden.value = String(id);
            hidden.disabled = !options.isPersonal();
            selectedMembersHidden.appendChild(hidden);
        });

        options.onRendered();
    }

    function renderResults(results: MemberSearchResult[]): void {
        if (!searchResults) return;
        searchResults.replaceChildren();
        const available = results.filter((member) => !selectedMembers.has(Number(member.id)));
        if (available.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'ad-member-results-empty';
            empty.textContent = '該当する会員がいません';
            searchResults.appendChild(empty);
        } else {
            available.forEach((member) => {
                const { name, number } = memberLabel(member);
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'ad-member-result';
                const copy = document.createElement('span');
                const strong = document.createElement('strong');
                strong.textContent = name;
                const small = document.createElement('small');
                small.textContent = number;
                copy.append(strong, small);
                const icon = document.createElement('i');
                icon.className = 'fas fa-plus-circle';
                icon.setAttribute('aria-hidden', 'true');
                button.append(copy, icon);
                button.addEventListener('click', () => {
                    selectedMembers.set(Number(member.id), `${name}（${number}）`);
                    render();
                    options.onSelectionChange();
                    searchResults.hidden = true;
                    if (searchInput) {
                        searchInput.value = '';
                        searchInput.focus();
                    }
                });
                searchResults.appendChild(button);
            });
        }
        searchResults.hidden = false;
    }

    memberCsv?.addEventListener('change', () => {
        const file = memberCsv.files?.[0];
        memberCsv.value = '';
        if (!file) return;
        const identifierLabel = document.body.dataset.memberIdentifierLabel || '会員番号';
        const reader = new FileReader();
        reader.onload = async () => {
            const lines = String(reader.result || '').split(/\r?\n/).map((line) => line.trim()).filter((line) => line !== '');
            const numbers = Array.from(new Set(lines.map((line, index) => {
                const cell = (line.split(',')[0] || '').trim().replace(/^"|"$/g, '');
                if (index === 0 && (cell === '会員番号' || cell === '会員ID' || cell.toLowerCase() === 'member_number' || cell.toLowerCase() === 'id')) return '';
                return cell;
            }).filter((number) => number !== '')));
            if (numbers.length === 0) {
                if (memberCsvMessage) {
                    memberCsvMessage.hidden = false;
                    memberCsvMessage.textContent = `${identifierLabel}が見つかりませんでした。`;
                }
                return;
            }
            try {
                const payload = await fetchJson<{ data?: MemberSearchResult[]; missing?: string[] }>(
                    `${options.membersUrl}?numbers=${encodeURIComponent(numbers.join(','))}`,
                );
                (payload.data || []).forEach((member) => {
                    const { name, number } = memberLabel(member);
                    selectedMembers.set(Number(member.id), `${name}（${number}）`);
                });
                render();
                options.onSelectionChange();
                if (memberCsvMessage) {
                    const missing = payload.missing || [];
                    memberCsvMessage.hidden = false;
                    memberCsvMessage.textContent = missing.length === 0
                        ? `${(payload.data || []).length}人を追加しました。`
                        : `${(payload.data || []).length}人を追加しました。見つからない${identifierLabel}: ${missing.join('、')}`;
                }
            } catch {
                if (memberCsvMessage) {
                    memberCsvMessage.hidden = false;
                    memberCsvMessage.textContent = 'CSVの読み込みに失敗しました。';
                }
            }
        };
        reader.readAsText(file);
    });

    searchInput?.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        const query = searchInput.value.trim();
        if (!searchResults) return;
        if (query === '') {
            searchResults.hidden = true;
            return;
        }
        searchTimer = window.setTimeout(async () => {
            try {
                const data = await fetchJson<{ data?: MemberSearchResult[] }>(`${options.membersUrl}?q=${encodeURIComponent(query)}`);
                renderResults(Array.isArray(data.data) ? data.data : []);
            } catch {
                renderResults([]);
            }
        }, 280);
    });

    return { render };
}
