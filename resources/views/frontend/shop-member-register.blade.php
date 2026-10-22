<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $projectName }} — 会員登録</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Kaku+Gothic+New:wght@500;700;900&display=swap" rel="stylesheet">
    <style>
      *, *::before, *::after { box-sizing: border-box; }
      body { font-family: "Zen Kaku Gothic New", "Hiragino Kaku Gothic ProN", "Yu Gothic", sans-serif; font-weight: 500; background: #f3f3f1; color: #141414; margin: 0; padding: 16px; }
      .shop-login { max-width: 480px; margin: 32px auto; padding: 28px 24px; background: #fff; border: 1px solid #e3e3e0; border-radius: 4px; }
      .shop-login h1 { font-size: 1.35rem; font-weight: 900; margin: 0 0 8px; padding-left: 10px; border-left: 4px solid #141414; line-height: 1.3; }
      .shop-login p { color: #6a6a6a; font-size: 14px; line-height: 1.6; }
      .shop-login label, .shop-login legend { display: block; margin: 14px 0 4px; font-size: 13px; font-weight: 700; color: #6a6a6a; }
      .shop-login input, .shop-login select, .shop-login textarea { width: 100%; padding: 12px; font: inherit; font-size: 16px; border: 1px solid #b5b5b0; border-radius: 4px; background: #fff; }
      .shop-login input[readonly] { background: #f3f3f1; }
      .shop-login fieldset { border: 0; margin: 0; padding: 0; }
      .shop-login .choice { display: flex; align-items: center; gap: 8px; margin: 6px 0; font-weight: 500; color: #141414; }
      .shop-login .choice input { width: auto; }
      .shop-login .hint { margin: 4px 0 0; font-size: 12px; }
      .shop-login .required { color: #b42318; }
      .shop-login input:focus-visible, .shop-login select:focus-visible, .shop-login textarea:focus-visible, .shop-login button:focus-visible { outline: 2px solid #141414; outline-offset: 2px; }
      .shop-login button { display: block; width: 100%; margin-top: 20px; padding: 14px; border: 0; border-radius: 4px; background: #141414; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
      .shop-login .btn-muted { background: #e8e8e5; color: #141414; margin-top: 12px; }
      .shop-login .error { color: #b42318; font-size: 13px; }
    </style>
</head>
<body>
    @php
        $emailLocked = $user->email_verified_at !== null && (string) $user->email !== '';
        $notificationLabels = ['all' => 'すべて受け取る', 'coupon_only' => 'クーポン情報のみ', 'none' => '受け取らない'];
    @endphp
    <div class="shop-login">
        <h1>{{ $projectName }} 会員登録</h1>
        <p>ご利用には会員登録が必要です。登録した内容はアプリでも使えます。</p>

        @if ($errors->any())
            <div class="error" role="alert">
                @foreach ($errors->all() as $message)
                    <p class="error">{{ $message }}</p>
                @endforeach
            </div>
        @endif

        <form method="post" action="{{ $submitUrl }}">
            @csrf
            @foreach ($fields as $field)
                @php
                    $key = (string) ($field['key'] ?? '');
                    $label = (string) ($field['label'] ?? $key);
                    $required = (bool) ($field['required'] ?? false);
                    $type = (string) ($field['input_type'] ?? 'text');
                    $choices = is_array($field['values'] ?? null) ? $field['values'] : [];
                    $id = 'field_'.preg_replace('/[^a-z0-9_]/i', '_', $key);
                    $default = match ($key) {
                        'name' => (string) ($user->name ?? ''),
                        'email' => (string) ($user->email ?? ''),
                        'notification_preference' => 'all',
                        default => '',
                    };
                    $value = old($key, $default);
                @endphp
                @continue($key === '')

                @if ($type === 'checkbox' && $choices !== [])
                    <fieldset>
                        <legend>{{ $label }}@if($required) <span class="required">*</span>@endif</legend>
                        @foreach ($choices as $index => $choice)
                            <label class="choice" for="{{ $id }}_{{ $index }}">
                                <input type="checkbox" id="{{ $id }}_{{ $index }}" name="{{ $key }}[]" value="{{ $choice }}" @checked(in_array((string) $choice, (array) old($key, []), true))>
                                {{ $choice }}
                            </label>
                        @endforeach
                    </fieldset>
                @else
                    <label for="{{ $id }}">{{ $label }}@if($required || $key === 'notification_preference') <span class="required">*</span>@endif</label>
                    @if ($key === 'sex' || $key === 'mail_receive_flag' || $key === 'customer_store_id' || $key === 'notification_preference' || (in_array($type, ['radio', 'selectbox'], true) && $choices !== []))
                        @php
                            $options = match (true) {
                                $key === 'sex' => ['1' => '男性', '2' => '女性'],
                                $key === 'mail_receive_flag' => ['1' => '許可する', '0' => '許可しない'],
                                $key === 'customer_store_id' => collect($field['options'] ?? [])->mapWithKeys(fn ($option) => [(string) $option['value'] => (string) $option['label']])->all(),
                                $key === 'notification_preference' => array_intersect_key($notificationLabels, array_flip($choices)),
                                default => array_combine(array_map('strval', $choices), array_map('strval', $choices)),
                            };
                        @endphp
                        <select id="{{ $id }}" name="{{ $key }}" @required($required || $key === 'notification_preference')>
                            @if ($key !== 'notification_preference')
                                <option value="">未選択</option>
                            @endif
                            @foreach ($options as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                    @elseif ($type === 'textarea' || $key === 'address')
                        <textarea id="{{ $id }}" name="{{ $key }}" rows="3" maxlength="{{ $key === 'address' ? 85 : 500 }}" @required($required)>{{ $value }}</textarea>
                    @elseif ($key === 'birthday')
                        <input type="date" id="{{ $id }}" name="{{ $key }}" value="{{ $value }}" @required($required)>
                    @elseif ($key === 'email')
                        <input type="email" id="{{ $id }}" name="{{ $key }}" value="{{ $value }}" maxlength="255" autocomplete="email" @required($required) @readonly($emailLocked)>
                        @if ($emailLocked)
                            <p class="hint">ログインに使うメールアドレスです。</p>
                        @endif
                    @elseif ($key === 'postal_code')
                        <input type="text" id="{{ $id }}" name="{{ $key }}" value="{{ $value }}" maxlength="8" inputmode="numeric" autocomplete="postal-code" placeholder="例: 100-0001" @required($required)>
                    @else
                        <input type="{{ $key === 'phone' ? 'tel' : 'text' }}" id="{{ $id }}" name="{{ $key }}" value="{{ $value }}" maxlength="{{ $key === 'phone' ? 20 : 255 }}" @if($key === 'name') autocomplete="name" @elseif($key === 'phone') autocomplete="tel" @endif @required($required)>
                    @endif
                @endif
                @if (! empty($field['description']))
                    <p class="hint">{{ $field['description'] }}</p>
                @endif
            @endforeach
            <button type="submit">登録する</button>
        </form>
        <form method="post" action="{{ $logoutUrl }}">
            @csrf
            <button type="submit" class="btn-muted">ログアウト</button>
        </form>
    </div>
</body>
</html>
