/** アンケート作成画面の質問ビルダー（追加・複製・並べ替え・選択肢の編集）と、会員アプリでの見え方のプレビュー。 */

const OPTION_TYPES = ['choice', 'multi_choice'];
const OTHER_LABEL = 'その他（自由入力）';

type PreviewQuestion = { type: string; label: string; help: string; options: string[]; allowOther: boolean; required: boolean };

function el<K extends keyof HTMLElementTagNameMap>(tag: K, className = '', text?: string): HTMLElementTagNameMap[K] {
    const node = document.createElement(tag);
    if (className) node.className = className;
    // 入力された文字は textContent で入れ、HTML として解釈させない
    if (text !== undefined) node.textContent = text;
    return node;
}

function fromTemplate(template: HTMLTemplateElement, replacements: Record<string, string>): HTMLElement {
    let html = template.innerHTML;
    for (const [key, value] of Object.entries(replacements)) html = html.split(key).join(value);
    const holder = document.createElement('div');
    holder.innerHTML = html.trim();
    return holder.firstElementChild as HTMLElement;
}

function scale(from: number, to: number): HTMLElement {
    const row = el('div', 'eg-sv-scale');
    row.style.gridTemplateColumns = `repeat(${to - from + 1}, 1fr)`;
    for (let score = from; score <= to; score++) row.append(el('span', '', String(score)));
    return row;
}

function previewField(question: PreviewQuestion): HTMLElement {
    switch (question.type) {
        case 'nps': return scale(0, 10);
        case 'rating': return el('div', 'eg-sv-stars', '★★★★★');
        case 'choice':
        case 'multi_choice': {
            const list = el('div');
            const options = [...question.options, ...(question.allowOther ? [OTHER_LABEL] : [])];
            for (const option of options.length ? options : ['選択肢']) {
                const row = el('div', 'eg-sv-option');
                row.append(el('i', question.type === 'choice' ? 'fa-regular fa-circle' : 'fa-regular fa-square'), el('span', '', option));
                list.append(row);
            }
            return list;
        }
        default: return el('div', 'eg-sv-input');
    }
}

function renderPreview(screen: HTMLElement, form: HTMLFormElement, questions: PreviewQuestion[]): void {
    const body = el('div', 'eg-sv');
    const value = (selector: string) => (form.querySelector<HTMLInputElement | HTMLTextAreaElement>(selector)?.value ?? '').trim();
    body.append(el('h3', 'eg-sv-title', value('[data-survey-title]') || 'タイトル'));
    const description = value('[data-survey-description]');
    if (description) body.append(el('p', 'eg-sv-desc', description));
    const reward = Number(value('[data-survey-reward]') || '0');
    if (reward > 0) body.append(el('span', 'eg-sv-reward', `回答で${reward}ポイント`));
    if (!questions.length) body.append(el('p', 'eg-sv-empty', '質問を追加すると、ここに表示されます'));
    questions.forEach((question, index) => {
        const card = el('div', 'eg-sv-q');
        card.append(el('strong', '', `Q${index + 1}. ${question.label || '質問文'}${question.required ? ' *' : ''}`));
        if (question.help) card.append(el('small', '', question.help));
        card.append(previewField(question));
        body.append(card);
    });
    screen.replaceChildren(body, el('div', 'eg-sv-submit', '回答を送信する'));
}

function mountSurveyBuilder(list: HTMLElement): void {
    const form = list.closest('form');
    const questionTemplate = document.querySelector<HTMLTemplateElement>('template[data-survey-question-template]');
    const optionTemplate = document.querySelector<HTMLTemplateElement>('template[data-survey-option-template]');
    const preview = form?.querySelector<HTMLElement>('[data-survey-preview]');
    if (!form || !questionTemplate || !optionTemplate) return;
    const addButtons = Array.from(form.querySelectorAll<HTMLButtonElement>('[data-add-type]'));
    const max = Number(list.dataset.max || '30');
    // 送信時の添字は並び順と無関係なので、追加ごとに重ならない番号を振る
    let next = 1000;
    const questions = () => Array.from(list.querySelectorAll<HTMLElement>(':scope > [data-q]'));

    const addOption = (question: HTMLElement): HTMLInputElement | null => {
        const option = fromTemplate(optionTemplate, { __Q__: question.dataset.index ?? '', __O__: String(next++) });
        question.querySelector('[data-q-options]')?.append(option);
        return option.querySelector('input');
    };

    const refresh = (): void => {
        const items = questions();
        const data: PreviewQuestion[] = [];
        let hasNps = false;
        items.forEach((question, position) => {
            const type = question.querySelector<HTMLSelectElement>('[data-q-type]')?.value ?? 'text';
            question.dataset.type = type;
            hasNps ||= type === 'nps';
            const number = question.querySelector('[data-q-number]');
            if (number) number.textContent = `Q${position + 1}`;
            data.push({
                type,
                label: question.querySelector<HTMLInputElement>('[data-q-label]')?.value.trim() ?? '',
                help: question.querySelector<HTMLInputElement>('[data-q-help]')?.value.trim() ?? '',
                options: Array.from(question.querySelectorAll<HTMLInputElement>('[data-q-option] input[name$="[label]"]')).map((input) => input.value.trim()).filter(Boolean),
                allowOther: OPTION_TYPES.includes(type) && (question.querySelector<HTMLInputElement>('[data-q-allow-other]')?.checked ?? false),
                required: question.querySelector<HTMLInputElement>('[data-q-required]')?.checked ?? false,
            });
        });
        for (const button of addButtons) {
            button.disabled = items.length >= max || (button.dataset.addType === 'nps' && hasNps);
        }
        if (preview) renderPreview(preview, form, data);
    };

    const addQuestion = (type: string): void => {
        const question = fromTemplate(questionTemplate, { __Q__: String(next++) });
        const select = question.querySelector<HTMLSelectElement>('[data-q-type]');
        if (select) select.value = type;
        const label = question.querySelector<HTMLInputElement>('[data-q-label]');
        if (type === 'nps' && label) label.value = list.dataset.npsLabel ?? '';
        list.append(question);
        refresh();
        label?.focus();
    };

    for (const button of addButtons) {
        button.addEventListener('click', () => addQuestion(button.dataset.addType ?? 'text'));
    }
    list.addEventListener('click', (event) => {
        const button = (event.target as HTMLElement).closest<HTMLButtonElement>('button');
        const question = button?.closest<HTMLElement>('[data-q]');
        if (!button || !question) return;
        if (button.matches('[data-q-add-option]')) {
            addOption(question)?.focus();
        } else if (button.matches('[data-q-remove-option]')) {
            button.closest('[data-q-option]')?.remove();
        } else if (button.matches('[data-q-remove]')) {
            question.remove();
        } else if (button.matches('[data-q-duplicate]')) {
            const copy = question.cloneNode(true) as HTMLElement;
            const index = String(next++);
            copy.dataset.index = index;
            copy.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>('[name]').forEach((field) => {
                field.name = field.name.replace(/^questions\[[^\]]+\]/, `questions[${index}]`);
            });
            // NPS は1問までなので、複製は選択式にする
            const select = copy.querySelector<HTMLSelectElement>('[data-q-type]');
            if (select?.value === 'nps') select.value = 'choice';
            question.after(copy);
        } else if (button.dataset.qMove === 'up' && question.previousElementSibling) {
            question.previousElementSibling.before(question);
        } else if (button.dataset.qMove === 'down' && question.nextElementSibling) {
            question.nextElementSibling.after(question);
        } else {
            return;
        }
        refresh();
    });
    list.addEventListener('change', (event) => {
        const target = event.target as HTMLElement;
        const question = target.closest<HTMLElement>('[data-q]');
        // NPS は1問まで。2問目に切り替えたら元に戻す
        if (question && target.matches('[data-q-type]')) {
            const select = target as HTMLSelectElement;
            if (select.value === 'nps' && questions().some((item) => item !== question && item.querySelector<HTMLSelectElement>('[data-q-type]')?.value === 'nps')) {
                select.value = question.dataset.type ?? 'choice';
            }
            const label = question.querySelector<HTMLInputElement>('[data-q-label]');
            if (select.value === 'nps' && label && label.value.trim() === '') label.value = list.dataset.npsLabel ?? '';
        }
        refresh();
    });
    form.addEventListener('input', refresh);
    refresh();
}

document.querySelectorAll<HTMLElement>('[data-survey-builder]').forEach(mountSurveyBuilder);
