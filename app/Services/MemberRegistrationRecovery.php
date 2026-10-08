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
        $attempt = MemberRegistrationAttempt::where('project_id', $member->project_id)
            ->where('user_id', $member->user_id)->first();
        if ($attempt === null || $attempt->member_number !== $member->member_number
            || $attempt->contract_id !== $this->smaregi->getContractIdForProject((int) $member->project_id)) {
            return;
        }
        $customer = $this->findCreatedCustomer($attempt);
        $id = $customer['customerId'] ?? null;
        if ($id === null || (string) $id === '') {
            return;
        }
        DB::transaction(function () use ($member, $attempt, $id): void {
            User::query()->whereKey($member->user_id)->lockForUpdate()->firstOrFail();
            $locked = Member::query()->whereKey($member->id)->lockForUpdate()->firstOrFail();
            $currentAttempt = MemberRegistrationAttempt::whereKey($attempt->id)->lockForUpdate()->first();
            if ($currentAttempt === null || $currentAttempt->registration_key !== $attempt->registration_key) {
                return;
            }
            if ((string) $locked->smaregi_customer_id === '' && $locked->member_number === $attempt->member_number) {
                $locked->update(['smaregi_customer_id' => (string) $id]);
                $attempt->delete();
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
