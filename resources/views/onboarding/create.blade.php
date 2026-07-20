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
<body class="admin-login-page onboarding-page">
    <div class="onboarding-shell">
        <header class="onboarding-header">
            <p class="onboarding-brand"><i class="fas fa-store"></i> Reggit</p>
            <h1 class="onboarding-title">プロジェクト入力ホーム</h1>
            <p class="onboarding-lead">プロジェクトと管理者アカウントを作成します。<br>スマレジ・デベロッパーズの契約情報を入力してください。</p>
        </header>

        <div id="onboarding-alert" class="alert alert-danger d-none" role="alert"></div>
        <div id="onboarding-success" class="alert alert-success d-none" role="alert"></div>

        <form id="onboarding-form" class="onboarding-form" action="{{ url('/api/onboarding/projects') }}" method="POST">
            @include('admin.projects._form')
            <div class="onboarding-actions">
                <button type="submit" class="btn btn-primary" id="onboarding-submit">プロジェクトを作成</button>
                <a href="{{ route('admin.login') }}" class="btn btn-secondary">ログイン画面へ</a>
            </div>
        </form>
    </div>

    <style>
        .onboarding-page {
            align-items: flex-start;
            justify-content: center;
            padding: 40px 16px 64px;
        }

        .onboarding-shell {
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
        }

        .onboarding-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .onboarding-brand {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0 auto 14px;
            padding: 6px 12px;
            border-radius: 999px;
            background: var(--primary-bg);
            color: var(--primary);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .onboarding-brand i {
            font-size: 12px;
        }

        .onboarding-title {
            margin: 0 0 10px;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: var(--letter-spacing-tight);
            line-height: 1.3;
        }

        .onboarding-lead {
            margin: 0 auto;
            max-width: 28em;
            font-size: 14px;
            line-height: 1.7;
            color: var(--text-secondary);
        }

        .onboarding-form .card {
            margin-top: 0;
        }

        .onboarding-form .card + .card {
            margin-top: 16px;
        }

        .onboarding-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-top: 24px;
            padding: 20px;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xs);
        }

        .onboarding-actions .btn {
            min-width: 180px;
        }

        .d-none {
            display: none !important;
        }

        @media (max-width: 560px) {
            .onboarding-page {
                padding: 28px 12px 48px;
            }

            .onboarding-title {
                font-size: 1.4rem;
            }

            .onboarding-actions {
                flex-direction: column;
            }

            .onboarding-actions .btn {
                width: 100%;
                min-width: 0;
            }
        }
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
