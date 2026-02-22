<?php

namespace App\UseCases\Contact;

use App\Models\ContactMessage;
use App\UseCases\Contact\Data\ContactStoreResult;
use App\Util\AdminContext;

class ContactStoreUseCase
{
    /** @param array<string, string|int|float|bool|array|null> $data お問い合わせフォームの入力（検証前） */
    public function handle(array $data): ContactStoreResult
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

        return new ContactStoreResult(true);
    }
}
