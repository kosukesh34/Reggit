<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Middleware;

class ValidateCsrfToken extends Middleware
{
    protected $except = [
        'api/payment/webhook',
        // スマレジ Webhook / 利用者契約通知（POST は外部サーバーからのため CSRF 対象外）
        'smaregi/webhook',
        'smaregi/idp',
    ];
}
