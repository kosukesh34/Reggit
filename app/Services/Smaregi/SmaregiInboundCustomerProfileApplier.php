<?php

namespace App\Services\Smaregi;

use App\Domain\Settings\Repositories\MemberSettings;
use App\Enums\ProfileCustomFieldInputType;
use App\Models\Member;
use App\Models\Store;

/** スマレジ会員の項目（会員コード・住所・氏名・独自項目など）をローカルの会員・ユーザーに書き込む。 */
class SmaregiInboundCustomerProfileApplier
{
    public function __construct(
        private MemberSettings $shopSettingRepository,
    ) {}

    /**
     * @param  array<string, string|int|float|bool|array<string, string|int|float|bool|null>|null>  $customer
     */
    public function apply(Member $member, array $customer): void
    {
        $memberUpdates = $this->buildMemberUpdates($member, $customer);
        if ($memberUpdates !== []) {
            $member->update($memberUpdates);
        }

        $user = $member->user;
        if ($user === null) {
            return;
        }
        $userUpdates = $this->buildUserUpdates($customer);
        if ($userUpdates !== []) {
            $user->update($userUpdates);
        }
    }

    /**
     * @param  array<string, string|int|float|bool|array<string, string|int|float|bool|null>|null>  $customer
     * @return array<string, string|array<string, string|list<string>|null>|null>
     */
    private function buildMemberUpdates(Member $member, array $customer): array
    {
        $updates = [];
        $customerCode = $this->stringOrNull($customer['customerCode'] ?? null);
        if ($customerCode !== null && $customerCode !== (string) $member->member_number && ! $this->memberNumberExists($member, $customerCode)) {
            $updates['member_number'] = $customerCode;
        }
        if (array_key_exists('postCode', $customer)) {
            $updates['postal_code'] = $this->stringOrNull($customer['postCode']);
        }
        if (array_key_exists('address', $customer)) {
            $updates['address'] = $this->stringOrNull($customer['address']);
        }
        if (array_key_exists('birthDate', $customer)) {
            $updates['birthday'] = $this->stringOrNull($customer['birthDate']);
        }
        if (array_key_exists('sex', $customer)) {
            $updates['sex'] = $this->stringOrNull($customer['sex']);
        }
        if (array_key_exists('mailReceiveFlag', $customer)) {
            $updates['mail_receive_flag'] = $this->stringOrNull($customer['mailReceiveFlag']);
        }
        if (isset($customer['storeId']) && (string) $customer['storeId'] !== '') {
            $storeId = Store::where('project_id', $member->project_id)->where('smaregi_store_id', (string) $customer['storeId'])->value('id');
            if ($storeId !== null) {
                $updates['customer_store_id'] = (int) $storeId;
            }
        }
        $extraUpdates = $this->profileExtraFromRemoteCustomFields($member, $customer);
        if ($extraUpdates !== []) {
            $updates['profile_extra'] = $extraUpdates;
        }

        return $updates;
    }

    /**
     * @param  array<string, string|int|float|bool|array<string, string|int|float|bool|null>|null>  $customer
     * @return array<string, string|list<string>|null>
     */
    private function profileExtraFromRemoteCustomFields(Member $member, array $customer): array
    {
        $remoteFields = $customer['customFields'] ?? null;
        if (! is_array($remoteFields) || $remoteFields === []) {
            return [];
        }
        $defs = $this->shopSettingRepository->getProfileFieldSettings((int) $member->project_id)['custom_fields'] ?? [];
        $keyByNo = [];
        $typeByKey = [];
        foreach ($defs as $cf) {
            if (! is_array($cf)) {
                continue;
            }
            $no = (int) ($cf['smaregi_no'] ?? 0);
            $key = (string) ($cf['key'] ?? '');
            if ($no < 1 || $key === '') {
                continue;
            }
            $keyByNo[$no] = $key;
            $typeByKey[$key] = (string) ($cf['input_type'] ?? '');
        }
        if ($keyByNo === []) {
            return [];
        }
        $extra = is_array($member->profile_extra) ? $member->profile_extra : [];
        $changed = false;
        foreach ($remoteFields as $field) {
            if (! is_array($field)) {
                continue;
            }
            $key = $keyByNo[(int) ($field['no'] ?? 0)] ?? '';
            if ($key === '') {
                continue;
            }
            $value = $this->fromSmaregiCustomFieldValue(
                $this->stringOrNull($field['value'] ?? null),
                $typeByKey[$key] ?? '',
            );
            if (($extra[$key] ?? null) === $value) {
                continue;
            }
            $extra[$key] = $value;
            $changed = true;
        }

        return $changed ? $extra : [];
    }

    /**
     * @return string|list<string>|null
     */
    private function fromSmaregiCustomFieldValue(?string $value, string $inputType): string|array|null
    {
        if ($value === null) {
            return null;
        }
        if ($inputType !== ProfileCustomFieldInputType::CHECKBOX) {
            return $value;
        }

        return array_values(array_filter(
            explode('、', $value),
            static fn (string $part): bool => $part !== '',
        ));
    }

    /**
     * @param  array<string, string|int|float|bool|array<string, string|int|float|bool|null>|null>  $customer
     * @return array<string, string|null>
     */
    private function buildUserUpdates(array $customer): array
    {
        $updates = [];
        $name = $this->joinCustomerName($customer, 'lastName', 'firstName');
        if ($name !== null) {
            $updates['name'] = $name;
        }
        $kana = $this->joinCustomerName($customer, 'lastKana', 'firstKana');
        if ($kana !== null) {
            $updates['kana'] = $kana;
        }
        if (array_key_exists('mailAddress', $customer)) {
            $updates['email'] = $this->stringOrNull($customer['mailAddress']);
        }
        if (array_key_exists('phoneNumber', $customer)) {
            $updates['phone'] = $this->stringOrNull($customer['phoneNumber']);
        }

        return $updates;
    }

    /**
     * @param  array<string, string|int|float|bool|array<string, string|int|float|bool|null>|null>  $customer
     */
    private function joinCustomerName(array $customer, string $lastKey, string $firstKey): ?string
    {
        if (! array_key_exists($lastKey, $customer) && ! array_key_exists($firstKey, $customer)) {
            return null;
        }
        $last = $this->stringOrNull($customer[$lastKey] ?? null);
        $first = $this->stringOrNull($customer[$firstKey] ?? null);
        $joined = trim(implode(' ', array_filter(
            [$last, $first],
            fn ($value) => $value !== null && ! in_array($value, [SmaregiCustomerUpdatePayloadBuilder::NAME_PLACEHOLDER, SmaregiCustomerUpdatePayloadBuilder::KANA_PLACEHOLDER], true),
        )));

        return $joined !== '' ? $joined : null;
    }

    /**
     * @param  string|int|float|bool|array<string, string|int|float|bool|null>|null  $value
     */
    private function stringOrNull($value): ?string
    {
        if (is_string($value)) {
            $value = trim($value);

            return $value !== '' ? $value : null;
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }

    private function memberNumberExists(Member $member, string $memberNumber): bool
    {
        return Member::query()
            ->where('project_id', $member->project_id)
            ->where('member_number', $memberNumber)
            ->where('id', '!=', $member->id)
            ->exists();
    }
}
