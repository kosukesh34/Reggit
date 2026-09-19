import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

type QueueRealtimeConfig = {
  projectKey: string;
  appKey: string;
  wsHost: string;
};

function readConfig(): QueueRealtimeConfig | null {
  const node = document.getElementById('queueRealtimeConfig');
  if (node === null || node.textContent === null) return null;
  try {
    return JSON.parse(node.textContent) as QueueRealtimeConfig;
  } catch {
    return null;
  }
}

function submitQueueForm(form: HTMLFormElement): void {
  const status = String(new FormData(form).get('status') ?? '');
  const label = status === 'called' ? '呼び出しますか？' : status === 'entered' ? '入店済みにしますか？' : 'キャンセルしますか？';
  if (!window.confirm(label)) return;
  const button = form.querySelector<HTMLButtonElement>('button[type="submit"]');
  if (button !== null) button.disabled = true;
  void fetch(form.action, {
    method: 'POST',
    body: new FormData(form),
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
  }).then((response) => {
    if (!response.ok) throw new Error('更新に失敗しました');
    window.setTimeout(() => window.location.reload(), 900);
  }).catch(() => {
    if (button !== null) button.disabled = false;
    window.alert('更新に失敗しました。通信状態を確認してください。');
  });
}

function bindSwipeRows(): void {
  document.querySelectorAll<HTMLTableRowElement>('[data-queue-swipe-row]').forEach((row) => {
    let startX = 0;
    let deltaX = 0;
    let tracking = false;
    row.addEventListener('pointerdown', (event) => {
      if ((event.target as HTMLElement).closest('button, a, input, form')) return;
      startX = event.clientX;
      deltaX = 0;
      tracking = true;
      row.setPointerCapture(event.pointerId);
    });
    row.addEventListener('pointermove', (event) => {
      if (!tracking) return;
      deltaX = Math.max(-120, Math.min(120, event.clientX - startX));
      row.style.transform = `translateX(${deltaX}px)`;
      row.classList.toggle('is-swipe-right', deltaX > 25);
      row.classList.toggle('is-swipe-left', deltaX < -25);
    });
    const finish = (): void => {
      if (!tracking) return;
      tracking = false;
      row.style.transform = '';
      row.classList.remove('is-swipe-right', 'is-swipe-left');
      const selector = deltaX > 80 ? '[data-swipe-primary]' : deltaX < -80 ? '[data-swipe-secondary]' : '';
      deltaX = 0;
      if (selector === '') return;
      const form = row.querySelector<HTMLFormElement>(selector);
      if (form !== null) submitQueueForm(form);
    };
    row.addEventListener('pointerup', finish);
    row.addEventListener('pointercancel', finish);
  });
}

function bindAjaxForms(): void {
  document.querySelectorAll<HTMLFormElement>('[data-queue-action-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      submitQueueForm(form);
    });
  });
}

function connectRealtime(config: QueueRealtimeConfig): void {
  if (config.appKey === '' || config.projectKey === '') return;
  const tunnelHost = config.wsHost.trim();
  const forceTLS = tunnelHost !== '' || window.location.protocol === 'https:';
  const port = tunnelHost !== '' ? 443 : (window.location.port ? Number(window.location.port) : (forceTLS ? 443 : 80));
  const echo = new Echo({
    broadcaster: 'reverb', key: config.appKey,
    wsHost: tunnelHost || window.location.hostname,
    wsPort: port, wssPort: port, forceTLS,
    enabledTransports: ['ws', 'wss'], Pusher,
  });
  echo.channel(`project.${config.projectKey}.data`).listen('.data.updated', (payload: { topic?: string }) => {
    if (payload.topic === 'queue' || payload.topic === 'all') window.location.reload();
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const config = readConfig();
  if (config === null) return;
  bindSwipeRows();
  bindAjaxForms();
  connectRealtime(config);
});
