<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TestMail;
use App\Util\LogHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class MailTestController extends Controller
{
    public function show(): View
    {
        return view('admin.mail-test', [
            'mailHost' => (string) config('mail.mailers.smtp.host'),
            'fromAddress' => (string) config('mail.from.address'),
            'fromName' => (string) config('mail.from.name'),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'email'],
        ]);

        $to = $validated['to'];

        try {
            Mail::to($to)->send(new TestMail(
                bodyMessage: 'これは Reggit からのテストメールです。受信できていれば SMTP 設定は正常です。',
            ));
        } catch (\Throwable $e) {
            LogHelper::error('[MailTest] send failed', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['to' => 'メールを送信できませんでした。SMTP 設定とログを確認してください。']);
        }

        LogHelper::info('[MailTest] send accepted by SMTP', [
            'to' => $to,
            'from' => (string) config('mail.from.address'),
            'host' => (string) config('mail.mailers.smtp.host'),
        ]);

        return redirect()
            ->route('system-admin.mail-test.show')
            ->with('status', $to.' へ SMTP 送信を受け付けました。届かない場合は迷惑メールフォルダと DMARC 設定を確認してください。');
    }
}
