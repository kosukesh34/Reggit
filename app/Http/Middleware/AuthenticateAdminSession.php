<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Session\Middleware\AuthenticateSession;

class AuthenticateAdminSession extends AuthenticateSession
{
    public function handle($request, Closure $next)
    {
        // Sessions created before password binding was enabled must log in again.
        if ($request->hasSession() && $request->user('admin') !== null
            && ! $this->guard()->viaRemember()
            && ! $request->session()->has('password_hash_admin')) {
            $this->logout($request);
        }

        return parent::handle($request, $next);
    }
}
