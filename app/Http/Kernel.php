<?php

namespace App\Http;

use App\Http\Middleware\AdminAuditLogger;
use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\AuthenticateKitchenToken;
use App\Http\Middleware\BindAdminProject;
use App\Http\Middleware\BindProjectFromRequest;
use App\Http\Middleware\CheckAdminPermission;
use App\Http\Middleware\EnsureStoreAdmin;
use App\Http\Middleware\EnsureSystemAdmin;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\SetAdminProjectFromQuery;
use App\Http\Middleware\ValidateCsrfToken;
use App\Http\Middleware\VerifySmaregiWebhookSecret;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class Kernel extends HttpKernel
{
    protected $middleware = [
        TrustProxies::class,
        HandleCors::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
    ];

    protected $middlewareGroups = [
        'web' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            ValidateCsrfToken::class,
            SubstituteBindings::class,
        ],

        'api' => [
            ThrottleRequests::class.':api',
            SubstituteBindings::class,
        ],
    ];

    protected $middlewareAliases = [
        'auth' => Authenticate::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'auth.session' => AuthenticateSession::class,
        'cache.headers' => SetCacheHeaders::class,
        'can' => Authorize::class,
        'guest' => RedirectIfAuthenticated::class,
        'password.confirm' => RequirePassword::class,
        'signed' => ValidateSignature::class,
        'system.admin' => EnsureSystemAdmin::class,
        'store.admin' => EnsureStoreAdmin::class,
        'set.admin.project' => SetAdminProjectFromQuery::class,
        'bind.admin.project' => BindAdminProject::class,
        'bind.project' => BindProjectFromRequest::class,
        'api.auth' => AuthenticateApiToken::class,
        'admin.audit' => AdminAuditLogger::class,
        'admin.permission' => CheckAdminPermission::class,
        'throttle' => ThrottleRequests::class,
        'verified' => EnsureEmailIsVerified::class,
        // 管理画面用
        'bind.admin.project' => BindAdminProject::class,
        'admin.audit' => AdminAuditLogger::class,
        'system.admin' => EnsureSystemAdmin::class,
        'admin.permission' => CheckAdminPermission::class,
        'api.token' => AuthenticateApiToken::class,
        'kitchen.auth' => AuthenticateKitchenToken::class,
        'verify.smaregi.webhook' => VerifySmaregiWebhookSecret::class,
    ];
}
