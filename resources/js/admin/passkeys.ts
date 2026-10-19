import { InvalidDomainError, NotSupportedError, Passkeys, UserCancelledError } from '@laravel/passkeys';
import QRCode from 'qrcode';

const csrf = (): string =>
  document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

const showError = (root: HTMLElement, message: string): void => {
  const el = root.querySelector<HTMLElement>('[data-passkey-error]');
  if (!el) return;
  el.hidden = message === '';
  el.textContent = message;
};

const showStatus = (root: HTMLElement, message: string): void => {
  const el = root.querySelector<HTMLElement>('[data-passkey-status]');
  if (!el) return;
  el.hidden = message === '';
  el.textContent = message;
};

const humanError = (error: unknown): string => {
  if (error instanceof UserCancelledError) {
    return 'パスキー操作がキャンセルされました。もう一度お試しください。';
  }
  if (error instanceof NotSupportedError) {
    return 'このブラウザはパスキーに対応していません。対応ブラウザで開き直してください。';
  }
  if (error instanceof InvalidDomainError) {
    return 'このURLではパスキーを使えません。アドレスバーのドメインで開き直すか、管理者に APP_URL / 公開URLの設定を確認してください。';
  }
  if (error instanceof Error && error.message.trim() !== '') {
    if (/rp id|relying party|invalid for this domain/i.test(error.message)) {
      return 'このURLではパスキーを使えません。今開いているドメイン（localhost なら localhost、ngrok なら ngrok）で開き直してください。';
    }
    return error.message;
  }
  return 'パスキーの処理に失敗しました。';
};

const configureFetch = (): void => {
  Passkeys.configure({
    fetch: {
      credentials: 'same-origin',
      headers: {
        'X-CSRF-TOKEN': csrf(),
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    },
  });
};

const setBusy = (button: HTMLButtonElement, busy: boolean, label: string): void => {
  button.disabled = busy;
  button.setAttribute('aria-busy', busy ? 'true' : 'false');
  button.innerHTML = busy
    ? `<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> ${label}`
    : button.dataset.idleHtml ?? button.innerHTML;
};

const bindVerify = (root: HTMLElement): void => {
  const button = root.querySelector<HTMLButtonElement>('[data-passkey-verify]');
  if (!button) return;
  button.dataset.idleHtml = button.innerHTML;
  const options = root.dataset.confirmOptions ?? '';
  const submit = root.dataset.confirmSubmit ?? '';
  const run = async (): Promise<void> => {
    showError(root, '');
    showStatus(root, '端末の確認画面を待っています…');
    setBusy(button, true, '確認中…');
    try {
      const response = await Passkeys.verify({
        routes: { options, submit },
      });
      showStatus(root, '確認できました。移動します…');
      const redirect = (response as { redirect?: string } | undefined)?.redirect;
      window.location.href = redirect || '/admin';
    } catch (error) {
      showStatus(root, '');
      showError(root, humanError(error));
      setBusy(button, false, '');
      button.innerHTML = button.dataset.idleHtml ?? button.innerHTML;
    }
  };
  button.addEventListener('click', () => {
    void run();
  });
  void run();
};

const bindRegister = (root: HTMLElement): void => {
  const button = root.querySelector<HTMLButtonElement>('[data-passkey-register]');
  const nameInput = root.querySelector<HTMLInputElement>('#passkey-name');
  if (!button || !nameInput) return;
  button.dataset.idleHtml = button.innerHTML;
  const options = root.dataset.registerOptions ?? '';
  const submit = root.dataset.registerSubmit ?? '';

  button.addEventListener('click', () => {
    void (async () => {
      const name = nameInput.value.trim();
      if (name === '') {
        showError(root, '端末名を入力してください。');
        nameInput.focus();
        return;
      }
      showError(root, '');
      showStatus(root, '端末の登録画面を待っています…');
      setBusy(button, true, '登録中…');
      try {
        const response = await Passkeys.register({
          name,
          routes: { options, submit },
        });
        showStatus(root, '登録できました。移動します…');
        const redirect = (response as { redirect?: string } | undefined)?.redirect;
        window.location.href = redirect || window.location.href;
      } catch (error) {
        showStatus(root, '');
        showError(root, humanError(error));
        setBusy(button, false, '');
        button.innerHTML = button.dataset.idleHtml ?? button.innerHTML;
      }
    })();
  });
};

const bindLoginSecurity = (form: HTMLElement): void => {
  const list = form.querySelector<HTMLElement>('[data-login-ip-list]');
  const currentRoot = form.querySelector<HTMLElement>('[data-login-current-ip]');
  if (list === null || currentRoot === null) return;

  const currentValueEl = currentRoot.querySelector<HTMLElement>('[data-login-current-ip-value]');
  const addCurrentButton = currentRoot.querySelector<HTMLButtonElement>('[data-login-ip-add-current]');
  const copyButton = currentRoot.querySelector<HTMLButtonElement>('[data-login-ip-copy]');
  const refreshButton = currentRoot.querySelector<HTMLButtonElement>('[data-login-ip-refresh]');
  const addButton = form.querySelector<HTMLButtonElement>('[data-login-ip-add]');

  const currentIp = (): string => (currentRoot.dataset.loginCurrentIp ?? '').trim();

  const setCurrentIp = (ip: string): void => {
    currentRoot.dataset.loginCurrentIp = ip;
    if (currentValueEl !== null) {
      currentValueEl.textContent = ip !== '' ? ip : '取得できませんでした';
    }
    if (addCurrentButton !== null) addCurrentButton.disabled = ip === '';
    if (copyButton !== null) copyButton.disabled = ip === '';
  };

  const existingValues = (): string[] =>
    Array.from(list.querySelectorAll<HTMLInputElement>('input[name="login_allowed_ips[]"]'))
      .map((input) => input.value.trim())
      .filter((value) => value !== '');

  const addRow = (value = '', focus = true): void => {
    const row = document.createElement('div');
    row.className = 'admin-login-ip-item';
    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'login_allowed_ips[]';
    input.className = 'form-control';
    input.placeholder = '例: 203.0.113.10';
    input.autocomplete = 'off';
    input.value = value;
    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'btn btn-sm btn-danger';
    remove.setAttribute('data-login-ip-remove', '');
    remove.setAttribute('aria-label', '削除');
    remove.innerHTML = '<i class="fas fa-times"></i>';
    row.append(input, remove);
    list.append(row);
    if (focus) input.focus();
  };

  addButton?.addEventListener('click', () => {
    addRow('');
  });

  addCurrentButton?.addEventListener('click', () => {
    const ip = currentIp();
    if (ip === '') return;
    if (existingValues().includes(ip)) {
      list.querySelectorAll<HTMLInputElement>('input[name="login_allowed_ips[]"]').forEach((input) => {
        if (input.value.trim() === ip) input.focus();
      });
      return;
    }
    addRow(ip);
  });

  copyButton?.addEventListener('click', () => {
    const ip = currentIp();
    if (ip === '') return;
    void navigator.clipboard.writeText(ip).then(() => {
      copyButton.textContent = 'コピーしました';
      window.setTimeout(() => {
        copyButton.textContent = 'コピー';
      }, 1500);
    }).catch(() => {
      window.prompt('現在のIPをコピーしてください', ip);
    });
  });

  refreshButton?.addEventListener('click', () => {
    const url = currentRoot.dataset.loginCurrentIpUrl ?? '';
    if (url === '') return;
    refreshButton.disabled = true;
    void fetch(url, {
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
      .then(async (response) => {
        if (!response.ok) throw new Error('failed');
        const data = (await response.json()) as { ip?: string };
        setCurrentIp((data.ip ?? '').trim());
      })
      .catch(() => {
        if (currentValueEl !== null) currentValueEl.textContent = '取得に失敗しました';
      })
      .finally(() => {
        refreshButton.disabled = false;
      });
  });

  list.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;
    const remove = target.closest<HTMLButtonElement>('[data-login-ip-remove]');
    if (remove === null) return;
    remove.closest('.admin-login-ip-item')?.remove();
  });
};

const bindRemoteEnroll = (root: HTMLElement): void => {
  const urlInput = root.querySelector<HTMLInputElement>('[data-remote-enroll-url]');
  const copyButton = root.querySelector<HTMLButtonElement>('[data-remote-enroll-copy]');
  const qrRoot = root.querySelector<HTMLElement>('[data-remote-enroll-qr]');
  const url = urlInput?.value.trim() ?? '';
  if (url !== '' && qrRoot !== null) {
    const canvas = document.createElement('canvas');
    canvas.setAttribute('aria-label', qrRoot.getAttribute('aria-label') || 'QRコード');
    void QRCode.toCanvas(canvas, url, {
      width: 200,
      margin: 2,
      errorCorrectionLevel: 'M',
    }).then(() => {
      qrRoot.replaceChildren(canvas);
    }).catch(() => {
      qrRoot.textContent = 'QRコードを表示できませんでした。上のリンクをコピーしてください。';
    });
  }
  copyButton?.addEventListener('click', () => {
    if (urlInput === null || url === '') return;
    void navigator.clipboard.writeText(url).then(() => {
      copyButton.textContent = 'コピーしました';
    }).catch(() => {
      urlInput.focus();
      urlInput.select();
    });
  });
};

document.addEventListener('DOMContentLoaded', () => {
  const loginSecurity = document.querySelector<HTMLElement>('[data-login-security-form]');
  if (loginSecurity) bindLoginSecurity(loginSecurity);
  const remote = document.querySelector<HTMLElement>('[data-passkey-remote-enroll]');
  if (remote) bindRemoteEnroll(remote);

  if (!Passkeys.isSupported()) {
    document.querySelectorAll<HTMLElement>('[data-passkey-error]').forEach((el) => {
      el.hidden = false;
      el.textContent = 'このブラウザはパスキーに対応していません。対応ブラウザで再度ログインしてください。';
    });
    document.querySelectorAll<HTMLButtonElement>('[data-passkey-register], [data-passkey-verify]').forEach((button) => {
      button.disabled = true;
    });
    return;
  }
  configureFetch();
  const verify = document.querySelector<HTMLElement>('[data-admin-passkey-verify]');
  if (verify) bindVerify(verify);
  const enroll = document.querySelector<HTMLElement>('[data-admin-passkey-enroll]');
  if (enroll) bindRegister(enroll);
});
