<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>プロジェクト作成 - Reggit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin/admin.css') }}">
</head>
<body class="admin-login-page">
    <div class="admin-container onboarding-container">
        <div class="page-header system-admin-page-header">
            <h1 class="page-title">プロジェクト入力ホーム</h1>
            <p class="page-header-desc">プロジェクトと管理者アカウントを作成します。スマレジ・デベロッパーズの契約情報を入力してください。</p>
        </div>

        <div id="onboarding-alert" class="alert alert-danger d-none" role="alert"></div>
        <div id="onboarding-success" class="alert alert-success d-none" role="alert"></div>

        <form id="onboarding-form" action="{{ url('/api/onboarding/projects') }}" method="POST">
            @include('admin.projects._form')
            <div class="form-actions mt-3">
                <button type="submit" class="btn btn-primary" id="onboarding-submit">プロジェクトを作成</button>
                <a href="{{ route('admin.login') }}" class="btn btn-secondary">ログイン画面へ</a>
            </div>
        </form>
    </div>

    <style>
        .onboarding-container { max-width: 720px; margin: 0 auto; padding: 24px 16px; }
        .onboarding-container .page-title { font-size: 1.5rem; margin-bottom: 0.5rem; }
        .d-none { display: none !important; }
    </style>

    <script>
        (function () {
            const form = document.getElementById('onboarding-form');
            const alertEl = document.getElementById('onboarding-alert');
            const successEl = document.getElementById('onboarding-success');
            const submitBtn = document.getElementById('onboarding-submit');

            function showError(message) {
                alertEl.textContent = message;
                alertEl.classList.remove('d-none');
                successEl.classList.add('d-none');
            }

            function showSuccess(message) {
                successEl.textContent = message;
                successEl.classList.remove('d-none');
                alertEl.classList.add('d-none');
            }

            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                alertEl.classList.add('d-none');
                successEl.classList.add('d-none');
                submitBtn.disabled = true;

                const formData = new FormData(form);
                const payload = {};
                formData.forEach(function (value, key) {
                    if (key === 'is_active') {
                        payload[key] = true;
                        return;
                    }
                    payload[key] = value;
                });
                if (!formData.has('is_active')) {
                    payload.is_active = false;
                }

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json().catch(function () { return {}; });

                    if (!response.ok) {
                        if (data.errors) {
                            const messages = Object.values(data.errors).flat();
                            showError(messages.join(' '));
                        } else {
                            showError(data.message || '作成に失敗しました。入力内容を確認してください。');
                        }
                        submitBtn.disabled = false;
                        return;
                    }

                    showSuccess('プロジェクトを作成しました。ログイン画面へ移動します…');
                    window.setTimeout(function () {
                        window.location.href = data.login_url || '{{ route('admin.login') }}';
                    }, 800);
                } catch (error) {
                    showError('通信エラーが発生しました。時間をおいて再度お試しください。');
                    submitBtn.disabled = false;
                }
            });
        })();
    </script>
</body>
</html>
