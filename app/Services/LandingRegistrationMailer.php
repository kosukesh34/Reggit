<?php

namespace App\Services;

use App\Mail\LandingFormUrlMail;
use App\Mail\LandingReadyMail;
use App\Mail\LandingVerificationMail;
use App\Models\LandingRegistration;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class LandingRegistrationMailer
{
    public function sendVerification(LandingRegistration $registration): void
    {
        Mail::to($registration->email)->send(new LandingVerificationMail(
            URL::temporarySignedRoute(
                'landing.register.verify',
                now()->addHours(LandingRegistration::VERIFY_EXPIRES_HOURS),
                ['registration' => $registration->id],
            ),
        ));
    }

    public function sendFormUrl(LandingRegistration $registration): string
    {
        $formUrl = URL::temporarySignedRoute(
            'landing.register.form',
            now()->addDays(LandingRegistration::FORM_EXPIRES_DAYS),
            ['registration' => $registration->id],
        );
        Mail::to($registration->email)->send(new LandingFormUrlMail($formUrl));
        $registration->form_url_sent_at = now();
        $registration->save();

        return $formUrl;
    }

    public function sendReady(
        string $email,
        string $storeName,
        string $liffUrl,
        string $loginUrl,
        string $loginId,
        string $password,
    ): void {
        Mail::to($email)->send(new LandingReadyMail(
            $storeName,
            $liffUrl,
            $loginUrl,
            $loginId,
            $password,
        ));
    }
}
