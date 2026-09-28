<?php

namespace Tests\Feature;

use Tests\TestCase;

class MailTestControllerTest extends TestCase
{
    public function test_mail_test_has_been_removed(): void
    {
        $this->get('/system-admin/mail-test')->assertNotFound();
        $this->post('/system-admin/mail-test')->assertNotFound();
    }
}
