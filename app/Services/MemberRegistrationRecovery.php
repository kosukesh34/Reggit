<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberRegistrationAttempt;
use App\Models\User;
use App\Services\Smaregi\SmaregiApiService;
use Illuminate\Support\Facades\DB;

/** Reuses only externally created customers bearing this durable attempt's marker. */
class MemberRegistrationRecovery
{
    public function __construct(private SmaregiApiService $smaregi) {}

    public function createOrRecover(MemberRegistrationAttempt $attempt, array $body): ?string
    {
        $customer = $this->findCreatedCustomer($attempt);
        if ($customer === null) {
            $body['note2'] = $this->marker($attempt);
            $customer = $this->smaregi->createCustomer($attempt->contract_id, $body);
        }
        $id = $customer['customerId'] ?? null;

        return $id !== null && (string) $id !== '' ? (string) $id : null;
    }

    /** Optional local registration may have succeeded after an ambiguous remote response. */
    public function recoverForMember(Member $member): void
    {
        if ((string) $member->smaregi_customer_id !== '') {
            return;
        }
        $projectId = (int) $member->project_id;
        $userId = (int) $member->user_id;
        $attempt = MemberRegistrationAttempt::where('project_id', $projectId)
            ->where('user_id', $userId)->first();
        if ($attempt === null || $attempt->member_number !== $member->member_number
            || $attempt->contract_id !== $this->smaregi->getContractIdForProject((int) $member->project_id)) {
            return;
        }
        $customer = $this->findCreatedCustomer($attempt);
        $id = $customer['customerId'] ?? null;
        if ($id === null || (string) $id === '') {
            return;
        }
        DB::transaction(function () use ($member, $attempt, $id, $projectId, $userId): void {
            $user = User::query()->whereKey($userId)->where('project_id', $projectId)->lockForUpdate()->first();
            if ($user === null) {
                return;
            }
            $locked = Member::query()->whereKey($member->id)->where('project_id', $projectId)
                ->where('user_id', $userId)->lockForUpdate()->first();
            if ($locked === null) {
                return;
            }
            $currentAttempt = MemberRegistrationAttempt::whereKey($attempt->id)->lockForUpdate()->first();
            if ($currentAttempt === null || $currentAttempt->registration_key !== $attempt->registration_key
                || (int) $currentAttempt->project_id !== $projectId || (int) $currentAttempt->user_id !== $userId
                || $currentAttempt->member_number !== $attempt->member_number
                || $currentAttempt->contract_id !== $attempt->contract_id
                || $attempt->contract_id !== $this->smaregi->getContractIdForProject($projectId)) {
                return;
            }
            if ((string) $locked->smaregi_customer_id === '' && $locked->member_number === $attempt->member_number) {
                $locked->update(['smaregi_customer_id' => (string) $id]);
                $currentAttempt->delete();
            }
        });
    }

    private function findCreatedCustomer(MemberRegistrationAttempt $attempt): ?array
    {
        $this->smaregi->clearLastErrorDetail();
        $customer = $this->smaregi->getCustomerByCode($attempt->contract_id, $attempt->member_number);
        if ($customer === null) {
            if (($this->smaregi->getLastHttpStatus() ?? 0) >= 400 || $this->smaregi->getLastErrorDetail() !== null) {
                throw new \RuntimeException('スマレジの登録状態を確認できません。再度お試しください。');
            }

            return null;
        }
        if ((string) ($customer['customerCode'] ?? '') !== $attempt->member_number
            || ! hash_equals($this->marker($attempt), (string) ($customer['note2'] ?? ''))) {
            throw new \RuntimeException('スマレジに同じ会員コードが存在します。管理者にご確認ください。');
        }

        return $customer;
    }

    private function marker(MemberRegistrationAttempt $attempt): string
    {
        return 'reggit-registration:'.$attempt->registration_key;
    }
}
