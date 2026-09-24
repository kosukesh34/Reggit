<?php

namespace App\Services;

use App\Enums\MemberNotificationPreference;
use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\Project;
use App\Models\Scopes\ProjectScope;
use App\Models\User;
use App\Util\AdminContext;
use Illuminate\Support\Str;

/** 一致したスマレジ会員を、このアプリの会員として結びつける。 */
final class ExistingMemberLinkWriter
{
    /** @param  array<string, mixed>  $customer */
    public function link(int $projectId, ?User $user, array $customer): Member
    {
        $project = Project::query()->find($projectId);
        if ($project !== null) {
            AdminContext::setCurrentProject($project);
        }
        $owner = $user ?? $this->createUser($projectId, $customer);
        $customerId = (string) $customer['customerId'];
        $member = $owner->member;
        if ($member === null) {
            return Member::query()->create([
                'project_id' => $projectId,
                'user_id' => $owner->id,
                'member_number' => $this->memberNumber($owner->id, (string) ($customer['customerCode'] ?? '')),
                'smaregi_customer_id' => $customerId,
                'points' => (int) ($customer['point'] ?? 0),
                'status' => MemberStatus::ACTIVE,
                'rank' => 'bronze',
                'birthday' => $this->birthday($customer),
                'address' => $this->nullableText((string) ($customer['address'] ?? '')),
                'notification_preference' => MemberNotificationPreference::ALL,
            ]);
        }
        $member->forceFill([
            'smaregi_customer_id' => $customerId,
            'points' => (int) ($customer['point'] ?? $member->points),
            'birthday' => $member->birthday ?? $this->birthday($customer),
            'address' => $member->address ?? $this->nullableText((string) ($customer['address'] ?? '')),
        ])->save();

        return $member;
    }

    /** @param  array<string, mixed>  $customer */
    private function createUser(int $projectId, array $customer): User
    {
        return User::query()->create([
            'project_id' => $projectId,
            'line_user_id' => 'guest-link-'.Str::lower(Str::random(16)),
            'name' => trim(((string) ($customer['lastName'] ?? '')).' '.((string) ($customer['firstName'] ?? ''))),
            'email' => $this->nullableText((string) ($customer['mailAddress'] ?? '')),
            'phone' => $this->nullableText((string) ($customer['phoneNumber'] ?? '')),
        ]);
    }

    private function memberNumber(int $userId, string $customerCode): string
    {
        $number = trim($customerCode);
        if ($number === '' || $this->numberTaken($number)) {
            $number = 'L'.$userId;
        }
        if ($this->numberTaken($number)) {
            $number = 'L'.$userId.'-'.Str::lower(Str::random(4));
        }

        return $number;
    }

    private function numberTaken(string $number): bool
    {
        return Member::withoutGlobalScope(ProjectScope::class)->where('member_number', $number)->exists();
    }

    /** @param  array<string, mixed>  $customer */
    private function birthday(array $customer): ?string
    {
        $value = trim((string) ($customer['birthDate'] ?? ''));
        if (preg_match('/^\d{4}[\/-]\d{2}[\/-]\d{2}/', $value) !== 1) {
            return null;
        }

        return str_replace('/', '-', substr($value, 0, 10));
    }

    private function nullableText(string $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
