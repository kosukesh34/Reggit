<?php

namespace App\Services;

use App\Enums\ExistingMemberLinkField;
use InvalidArgumentException;

/** 店舗が選んだ項目がすべて一致するスマレジ会員を1件に絞る。 */
final class ExistingMemberLinkMatcher
{
    /**
     * @param  list<array<string, mixed>>  $customers
     * @param  list<string>  $fields
     * @param  array<string, string>  $input
     * @return array<string, mixed>
     */
    public function match(array $customers, array $fields, array $input): array
    {
        $matched = [];
        foreach ($customers as $customer) {
            if ($this->matches($customer, $fields, $input)) {
                $matched[] = $customer;
            }
        }
        if ($matched === []) {
            throw new InvalidArgumentException('一致する会員が見つかりません');
        }
        if (count($matched) > 1) {
            throw new InvalidArgumentException('一致する会員が複数います。店舗にお問い合わせください');
        }

        return $matched[0];
    }

    /**
     * @param  array<string, mixed>  $customer
     * @param  list<string>  $fields
     * @param  array<string, string>  $input
     */
    private function matches(array $customer, array $fields, array $input): bool
    {
        foreach ($fields as $field) {
            if ($this->same($field, $input[$field] ?? '', $customer) === false) {
                return false;
            }
        }

        return $fields !== [];
    }

    /** @param  array<string, mixed>  $customer */
    private function same(string $field, string $input, array $customer): bool
    {
        $expected = $this->normalize($field, $input);
        if ($expected === '') {
            return false;
        }
        $actual = match ($field) {
            ExistingMemberLinkField::CUSTOMER_CODE => $this->normalize($field, (string) ($customer['customerCode'] ?? '')),
            ExistingMemberLinkField::EMAIL => $this->normalize($field, (string) ($customer['mailAddress'] ?? '')),
            ExistingMemberLinkField::PHONE => $this->normalize($field, (string) ($customer['phoneNumber'] ?? '')),
            ExistingMemberLinkField::NAME => $this->normalize($field, trim(((string) ($customer['lastName'] ?? '')).((string) ($customer['firstName'] ?? '')))),
            ExistingMemberLinkField::BIRTHDAY => $this->normalize($field, (string) ($customer['birthDate'] ?? '')),
            ExistingMemberLinkField::ADDRESS => $this->normalize($field, (string) ($customer['address'] ?? '')),
            default => '',
        };

        return $actual !== '' && $actual === $expected;
    }

    private function normalize(string $field, string $value): string
    {
        $value = trim($value);
        if ($field === ExistingMemberLinkField::PHONE) {
            return preg_replace('/\D+/', '', $value) ?? '';
        }
        if ($field === ExistingMemberLinkField::EMAIL) {
            return strtolower($value);
        }
        if ($field === ExistingMemberLinkField::NAME || $field === ExistingMemberLinkField::ADDRESS) {
            return str_replace([' ', '　'], '', $value);
        }

        return $value;
    }
}
