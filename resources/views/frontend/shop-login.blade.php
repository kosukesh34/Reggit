<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="project-key" content="{{ $projectKey }}">
    <title>{{ $projectName }} — {{ $mode === 'register' ? '新規登録' : 'ログイン' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Kaku+Gothic+New:wght@500;700;900&display=swap" rel="stylesheet">
    <style>
      *, *::before, *::after { box-sizing: border-box; }
      body { font-family: "Zen Kaku Gothic New", "Hiragino Kaku Gothic ProN", "Yu Gothic", sans-serif; font-weight: 500; background: #f3f3f1; color: #141414; margin: 0; padding: 16px; }
      .shop-login { max-width: 420px; margin: 32px auto; padding: 28px 24px; background: #fff; border: 1px solid #e3e3e0; border-radius: 4px; }
      .shop-login h1 { font-size: 1.35rem; font-weight: 900; margin: 0 0 8px; padding-left: 10px; border-left: 4px solid #141414; line-height: 1.3; }
      .shop-login p { color: #6a6a6a; font-size: 14px; line-height: 1.6; }
      .shop-login label { display: block; margin: 14px 0 4px; font-size: 13px; font-weight: 700; color: #6a6a6a; }
      .shop-login input { width: 100%; padding: 12px; font: inherit; font-size: 16px; border: 1px solid #b5b5b0; border-radius: 4px; }
      .shop-login input:focus-visible, .shop-login button:focus-visible, .shop-login a:focus-visible { outline: 2px solid #141414; outline-offset: 2px; }
      .shop-login button, .shop-login .btn { display: block; width: 100%; margin-top: 12px; padding: 14px; border: 0; border-radius: 4px; background: #06c755; color: #fff; font: inherit; font-weight: 700; text-align: center; text-decoration: none; cursor: pointer; }
      .shop-login .btn-secondary { background: #141414; }
      .shop-login .btn-muted { background: #e8e8e5; color: #141414; }
      .shop-login .error { color: #b42318; font-size: 13px; }
      .shop-login .ok { color: #047857; font-size: 13px; }
      .shop-login .divider { display: flex; align-items: center; gap: 12px; margin: 24px 0 12px; color: #6a6a6a; font-size: 12px; }
      .shop-login .divider::before, .shop-login .divider::after { content: ""; flex: 1; border-top: 1px solid #e3e3e0; }
      .shop-login .back { display: inline-block; margin-top: 24px; color: #141414; font-weight: 700; font-size: 14px; }
      .shop-login .mode-switch { display: flex; gap: 8px; margin: 0 0 20px; padding: 4px; background: #f3f3f1; border-radius: 4px; }
      .shop-login .mode-switch a { flex: 1; padding: 10px; text-align: center; text-decoration: none; color: #6a6a6a; font-weight: 700; font-size: 14px; border-radius: 3px; }
      .shop-login .mode-switch a[aria-current="page"] { background: #fff; color: #141414; box-shadow: 0 1px 2px rgba(0,0,0,.06); }
      .shop-login .method-list { display: grid; gap: 8px; margin: 0; padding: 0; list-style: none; }
      .shop-login .method-list button { margin: 0; background: #fff; color: #141414; border: 1px solid #b5b5b0; text-align: left; padding: 14px 16px; }
      .shop-login .method-list button[aria-pressed="true"] { border-color: #141414; background: #f8f8f6; }
      .shop-login .method-list strong { display: block; font-size: 15px; }
      .shop-login .method-list span { display: block; margin-top: 4px; color: #6a6a6a; font-size: 12px; font-weight: 500; }
      .shop-login .method-panel[hidden] { display: none; }
      .shop-login .alt-link { margin-top: 20px; font-size: 14px; }
      .shop-login .alt-link a { color: #141414; font-weight: 700; }
    </style>
</head>
<body>
    @php
        $isRegister = $mode === 'register';
        $loginHref = $loginUrl.(str_contains($loginUrl, '?') ? '&' : '?').'mode=login';
        $registerHref = $loginUrl.(str_contains($loginUrl, '?') ? '&' : '?').'mode=register';
    @endphp
    <div class="shop-login">
        <h1>{{ $projectName }}</h1>
        <div class="mode-switch" role="tablist" aria-label="ログインまたは新規登録">
            <a href="{{ $loginHref }}" @if(! $isRegister) aria-current="page" @endif>ログイン</a>
            <a href="{{ $registerHref }}" @if($isRegister) aria-current="page" @endif>新規登録</a>
        </div>
        <p>{{ $isRegister ? '初めてご利用の方は、メール・アプリ会員のコードで登録できます。' : 'ご利用の方法を選んでログインしてください。' }}</p>

        @if ($errors->any())
            <p class="error">{{ $errors->first() }}</p>
        @endif
        @if (session('success'))
            <p class="ok">{{ session('success') }}</p>
        @endif

        @unless($isRegister)
        <a class="btn" href="{{ $lineStartUrl }}">LINEでログイン</a>

        <div class="divider">または</div>
        @endunless

        <ul class="method-list" role="list">
            <li>
                <button type="button" data-method-select="email" aria-pressed="false">
                    <strong>{{ $isRegister ? 'メールで新規登録' : 'メールでログインリンク' }}</strong>
                    <span>{{ $isRegister ? '登録用のリンクをメールで送ります' : '登録済みメールへログインリンクを送ります' }}</span>
                </button>
            </li>
            <li>
                <button type="button" data-method-select="app" aria-pressed="false">
                    <strong>アプリでログイン</strong>
                    <span>アプリの会員引き継ぎで発行したコードを使います</span>
                </button>
            </li>
            @unless($isRegister)
            <li>
                <button type="button" data-method-select="passkey" aria-pressed="false">
                    <strong>パスキーでログイン</strong>
                    <span>この端末に登録したパスキーで入ります</span>
                </button>
            </li>
            @endunless
        </ul>

        <div class="method-panel" data-method-panel="email" hidden>
            <form method="post" action="{{ $emailUrl }}">
                @csrf
                <input type="hidden" name="mode" value="{{ $isRegister ? 'register' : 'login' }}">
                <label for="email">メールアドレス</label>
                <input id="email" type="email" name="email" required maxlength="255" value="{{ old('email') }}" autocomplete="email">
                <button type="submit" class="btn-secondary">{{ $isRegister ? '登録用リンクを送る' : 'ログインリンクを送る' }}</button>
            </form>
        </div>

        <div class="method-panel" data-method-panel="app" hidden>
            <p>アプリの「会員引き継ぎ」でコードを発行し、会員IDとコードを入力してください（30分・1回限り）。同じ会員としてショップに入れます。</p>
            <form method="post" action="{{ $appLoginUrl }}">
                @csrf
                <label for="app-member-id">会員ID</label>
                <input id="app-member-id" type="text" name="member_id" inputmode="numeric" pattern="[0-9]+" required maxlength="20" value="{{ old('member_id') }}" autocomplete="off">
                <label for="app-code">引き継ぎコード</label>
                <input id="app-code" type="text" name="code" required maxlength="32" value="{{ old('code') }}" autocomplete="one-time-code" autocapitalize="characters" spellcheck="false">
                <button type="submit" class="btn-secondary">アプリ会員で入る</button>
            </form>
        </div>

        @unless($isRegister)
        <div class="method-panel" data-method-panel="passkey" hidden>
            <label for="passkey-email">メール（任意・登録済みパスキーの絞り込み）</label>
            <input id="passkey-email" type="email" autocomplete="username webauthn">
            <button type="button" id="passkey-login" class="btn-muted">パスキーでログイン</button>
            <p id="passkey-error" class="error" hidden></p>
        </div>
        @endunless

        <p class="alt-link">
            @if($isRegister)
                アカウントをお持ちの方は <a href="{{ $loginHref }}">ログイン</a>
            @else
                初めての方は <a href="{{ $registerHref }}">新規登録</a>
            @endif
        </p>

        <a class="back" href="{{ $shopUrl }}">ショップに戻る</a>
    </div>
    <script type="module">
      const methodButtons = document.querySelectorAll('[data-method-select]');
      const panels = document.querySelectorAll('[data-method-panel]');
      methodButtons.forEach((button) => {
        button.addEventListener('click', () => {
          const method = button.getAttribute('data-method-select');
          methodButtons.forEach((item) => item.setAttribute('aria-pressed', item === button ? 'true' : 'false'));
          panels.forEach((panel) => {
            panel.hidden = panel.getAttribute('data-method-panel') !== method;
          });
        });
      });

      const button = document.getElementById('passkey-login');
      const errorEl = document.getElementById('passkey-error');
      if (button && errorEl) {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const optionsUrl = @json($passkeyOptionsUrl);
        const submitUrl = @json($passkeySubmitUrl);
        function b64urlToBuffer(value) {
          const pad = '='.repeat((4 - (value.length % 4)) % 4);
          const base64 = (value + pad).replace(/-/g, '+').replace(/_/g, '/');
          const raw = atob(base64);
          const out = new Uint8Array(raw.length);
          for (let i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
          return out.buffer;
        }
        function bufferToB64url(buffer) {
          const bytes = new Uint8Array(buffer);
          let str = '';
          bytes.forEach((b) => { str += String.fromCharCode(b); });
          return btoa(str).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
        }
        function prepareRequestOptions(options) {
          const next = structuredClone(options);
          next.challenge = b64urlToBuffer(next.challenge);
          if (Array.isArray(next.allowCredentials)) {
            next.allowCredentials = next.allowCredentials.map((c) => ({ ...c, id: b64urlToBuffer(c.id) }));
          }
          return next;
        }
        function serializeCredential(credential) {
          return {
            id: credential.id,
            rawId: bufferToB64url(credential.rawId),
            type: credential.type,
            response: {
              clientDataJSON: bufferToB64url(credential.response.clientDataJSON),
              authenticatorData: bufferToB64url(credential.response.authenticatorData),
              signature: bufferToB64url(credential.response.signature),
              userHandle: credential.response.userHandle ? bufferToB64url(credential.response.userHandle) : null,
            },
          };
        }

        button.addEventListener('click', async () => {
          errorEl.hidden = true;
          try {
            const email = document.getElementById('passkey-email').value.trim();
            const qs = email ? `?email=${encodeURIComponent(email)}` : '';
            const optionsRes = await fetch(optionsUrl + qs, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const optionsJson = await optionsRes.json();
            const assertion = await navigator.credentials.get({ publicKey: prepareRequestOptions(optionsJson.options) });
            const submitRes = await fetch(submitUrl, {
              method: 'POST',
              credentials: 'same-origin',
              headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
              },
              body: JSON.stringify({ credential: serializeCredential(assertion) }),
            });
            const body = await submitRes.json();
            if (!submitRes.ok) throw new Error(body.message || 'パスキーログインに失敗しました');
            window.location.href = body.redirect || @json($shopUrl);
          } catch (error) {
            errorEl.hidden = false;
            errorEl.textContent = error instanceof Error ? error.message : 'パスキーログインに失敗しました';
          }
        });
      }
    </script>
</body>
</html>
