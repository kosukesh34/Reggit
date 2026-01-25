<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateLineUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $lineUserId = $request->header('X-Line-User-Id');

        if (!$lineUserId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = User::where('line_user_id', $lineUserId)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}

