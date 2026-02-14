<?php

namespace App\UseCases\Contact;

use App\Models\ContactMessage;
use App\UseCases\Contact\Dto\ContactStoreResultDto;
use App\Util\AdminContext;

class ContactStoreUseCase
{
    /** @param array<string, mixed> $data */
    public function handle(array $data): ContactStoreResultDto
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;

        ContactMessage::create([
            'project_id' => $projectId,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'] ?? ($data['body'] ?? null),
            'payload' => $data,
        ]);

        return new ContactStoreResultDto(true);
    }
}
