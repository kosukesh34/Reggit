<?php

namespace Tests\Unit\Services\Line;

use App\Services\Line\LineService;
use Tests\TestCase;

class LineServiceTest extends TestCase
{
    public function test_verify_signature_with_secret_一致すればtrueであること(): void
    {
        $service = new LineService;
        $body = '{"events":[]}';
        $secret = 'channel-secret';
        $signature = base64_encode(hash_hmac('sha256', $body, $secret, true));

        $this->assertTrue($service->verifySignatureWithSecret($body, $signature, $secret));
        $this->assertFalse($service->verifySignatureWithSecret($body, $signature, 'other-secret'));
        $this->assertFalse($service->verifySignatureWithSecret($body, $signature, ''));
        $this->assertFalse($service->verifySignatureWithSecret($body, $signature, null));
        $this->assertFalse($service->verifySignatureWithSecret($body, null, $secret));
        $this->assertFalse($service->verifySignatureWithSecret($body, '', $secret));
    }
}
