<?php

namespace App\Http;

use App\Http\Middleware\AdminAuditLogger;
use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\AuthenticateStoreApp;
use App\Http\Middleware\AuthorizeQueueAccess;
use App\Http\Middleware\BindAdminProject;
use App\Http\Middleware\BindProjectFromRequest;
use App\Http\Middleware\CheckAdminPermission;
use App\Http\Middleware\CheckApiProject;
use App\Http\Middleware\EnsureProjectPlanFeature;
use App\Http\Middleware\EnsureStoreAdmin;
use App\Http\Middleware\EnsureSystemAdmin;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\ResetTenantContext;
use App\Http\Middleware\SetAdminProjectFromQuery;
use App\Http\Middleware\ValidateCsrfToken;
use App\Http\Middleware\VerifySmaregiIdpCallbackToken;
use App\Http\Middleware\VerifySmaregiWebhookSecret;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Contracts\Foundation\Application;
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
use Illuminate\Routing\Router;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class Kernel extends HttpKernel
{
    public function __construct(Application $app, Router $router)
    {
        parent::__construct($app, $router);

        // Resolve the tenant before implicit model binding executes its scope.
        $this->addToMiddlewarePriorityBefore(SubstituteBindings::class, BindProjectFromRequest::class);
        $this->addToMiddlewarePriorityBefore(SubstituteBindings::class, BindAdminProject::class);
        $this->addToMiddlewarePriorityBefore(SubstituteBindings::class, AuthenticateApiToken::class);
        $this->addToMiddlewarePriorityAfter(AuthenticateApiToken::class, CheckApiProject::class);
    }

    protected $middleware = [
        ResetTenantContext::class,
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
        'api.token' => AuthenticateApiToken::class,
        'admin.audit' => AdminAuditLogger::class,
        'admin.permission' => CheckAdminPermission::class,
        'admin.plan' => EnsureProjectPlanFeature::class,
        'throttle' => ThrottleRequests::class,
        'verified' => EnsureEmailIsVerified::class,
        'store-app.auth' => AuthenticateStoreApp::class,
        'queue.access' => AuthorizeQueueAccess::class,
        'verify.smaregi.webhook' => VerifySmaregiWebhookSecret::class,
        'verify.smaregi.idp' => VerifySmaregiIdpCallbackToken::class,
    ];
}
