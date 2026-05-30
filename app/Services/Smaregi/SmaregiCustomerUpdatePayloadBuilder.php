<?php

namespace App\Services\Smaregi;

/**
 * スマレジ pos/customers PATCH 用のリクエストボディを組み立てる。
 * 空文字や不正なメールアドレスを送ると API が 400 になるため、送信前に正規化する。
 */
class SmaregiCustomerUpdatePayloadBuilder
{
    /**
     * @param  array<string, mixed>  $data  name / kana / email / phone 等
     * @return array<string, string>
     */
    public function buildFromProfileData(array $data): array
    {
        $body = [];

        $name = trim((string) ($data['name'] ?? ''));
        if ($name !== '') {
            $parts = preg_split('/\s+/u', $name, 2);
            $body['lastName'] = $parts[0] ?? $name;
            $body['firstName'] = $parts[1] ?? '';
        }

        $kana = trim((string) ($data['kana'] ?? ''));
        if ($kana !== '') {
            $kanaParts = preg_split('/\s+/u', $kana, 2);
            $body['lastKana'] = $kanaParts[0] ?? $kana;
            $body['firstKana'] = $kanaParts[1] ?? '';
        }

        if (array_key_exists('email', $data)) {
            $email = trim((string) ($data['email'] ?? ''));
            if ($email !== '' && strtolower($email) !== 'null' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $body['mailAddress'] = $email;
            }
        }

        if (array_key_exists('phone', $data)) {
            $phone = trim((string) ($data['phone'] ?? ''));
            if ($phone !== '' && strtolower($phone) !== 'null') {
                $body['phoneNumber'] = $phone;
            }
        }

        return $body;
    }

    /**
     * 管理画面の会員編集用（ユーザー + 会員フィールド）。
     *
     * @param  array<string, mixed>  $userFields
     * @param  array<string, mixed>  $memberFields
     * @return array<string, string>
     */
    public function buildFromAdminMemberData(array $userFields, array $memberFields = []): array
    {
        $body = $this->buildFromProfileData($userFields);

        if (array_key_exists('member_number', $memberFields)) {
            $code = trim((string) ($memberFields['member_number'] ?? ''));
            if ($code !== '') {
                $body['customerCode'] = $code;
            }
        }
        if (array_key_exists('postal_code', $memberFields)) {
            $postCode = trim((string) ($memberFields['postal_code'] ?? ''));
            if ($postCode !== '') {
                $body['postCode'] = $postCode;
            }
        }
        if (array_key_exists('address', $memberFields)) {
            $address = trim((string) ($memberFields['address'] ?? ''));
            if ($address !== '') {
                $body['address'] = $address;
            }
        }
        if (array_key_exists('birthday', $memberFields)) {
            $birthday = $memberFields['birthday'];
            if ($birthday instanceof \DateTimeInterface) {
                $body['birthDate'] = $birthday->format('Y-m-d');
            } elseif (is_string($birthday) && trim($birthday) !== '') {
                $body['birthDate'] = trim($birthday);
            }
        }
        if (array_key_exists('sex', $memberFields)) {
            $sex = trim((string) ($memberFields['sex'] ?? ''));
            if ($sex !== '') {
                $body['sex'] = $sex;
            }
        }

        return $body;
    }
}
