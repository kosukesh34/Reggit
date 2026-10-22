<?php

namespace App\UseCases\ShopAuth;

use App\Mail\ShopMagicLinkMail;
use App\Util\AdminContext;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendShopMagicLinkUseCase
{
    public function handle(string $email, string $consumeRouteName, array $routeParams = []): void
    {
        $project = AdminContext::getCurrentProject();
        abort_unless($project !== null, 404);
        $email = strtolower(trim($email));
        $url = URL::temporarySignedRoute(
            $consumeRouteName,
            now()->addMinutes(30),
            array_merge($routeParams, ['email' => $email]),
        );
        Mail::to($email)->send(new ShopMagicLinkMail($url, (string) $project->name));
    }
}
