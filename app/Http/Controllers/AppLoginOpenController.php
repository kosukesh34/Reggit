<?php

namespace App\Http\Controllers;

use App\Support\NativeApp;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** メールのログインリンクからアプリを開く中継ページ。メールアプリは独自スキームを開けないため https を経由する。 */
class AppLoginOpenController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'project_key' => ['required', 'string', 'max:64'],
            'member_id' => ['required', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:32'],
        ]);

        return view('frontend.app-login', [
            'appUrl' => NativeApp::urlSchemePrefix().'login?'.http_build_query($validated),
            'memberId' => (int) $validated['member_id'],
            'code' => (string) $validated['code'],
        ]);
    }
}
