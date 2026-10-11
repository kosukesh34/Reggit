<?php

namespace App\UseCases\Member;

use App\Enums\ExistingMemberLinkField;
use App\Mail\ExistingMemberLinkMail;
use App\Models\ExistingMemberLinkRequest;
use App\Models\ExistingMemberLinkSetting;
use App\Models\Member;
use App\Models\User;
use App\Services\ExistingMemberLinkMatcher;
use App\Services\ExistingMemberLinkWriter;
use App\Services\Smaregi\SmaregiApiService;
use App\Util\LogHelper;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;

class RequestExistingMemberLinkUseCase
{
    public function __construct(
        private SmaregiApiService $smaregiApi,
        private ExistingMemberLinkMatcher $matcher,
        private ExistingMemberLinkWriter $writer,
    ) {}

    /**
     * @param  array<string, string>  $input
     * @return array{linked: bool, message: string}
     */
    public function handle(int $projectId, ?User $user, array $input): array
    {
        $setting = ExistingMemberLinkSetting::query()->where('project_id', $projectId)->first();
        if ($setting === null || $setting->is_enabled === false) {
            throw new InvalidArgumentException('既存会員連携は利用できません');
        }
        if ($user !== null && $user->member !== null && trim((string) $user->member->smaregi_customer_id) !== '') {
            throw new InvalidArgumentException('すでに既存会員連携済みです');
        }

        ['contract_id' => $contractId, 'customer' => $customer] = $this->findCustomer($projectId, $setting->identifier_fields, $input);
        $customerId = trim((string) ($customer['customerId'] ?? ''));
        if ($customerId === '') {
            throw new InvalidArgumentException('一致する会員が見つかりません');
        }
        if (Member::query()->where('project_id', $projectId)->where('smaregi_customer_id', $customerId)->exists()) {
            throw new InvalidArgumentException('この会員はすでに連携されています');
        }

        if ($user !== null) {
            $email = trim((string) ($customer['mailAddress'] ?? ''));
            if ($email === '') {
                throw new InvalidArgumentException('確認先のメールアドレスが未登録です。店舗にお問い合わせください');
            }

            return $this->sendMail($projectId, $user, $contractId, $customerId, $email);
        }

        $fields = is_array($setting->identifier_fields) ? $setting->identifier_fields : [];
        if (in_array(ExistingMemberLinkField::EMAIL, $fields, true)) {
            return $this->sendMail($projectId, null, $contractId, $customerId, (string) ($input[ExistingMemberLinkField::EMAIL] ?? ''));
        }

        $this->writer->link($projectId, null, $customer);

        return ['linked' => true, 'message' => '既存会員を連携しました'];
    }

    /**
     * @param  list<string>  $fields
     * @param  array<string, string>  $input
     * @return array{contract_id: string, customer: array<string, mixed>}
     */
    private function findCustomer(int $projectId, array $fields, array $input): array
    {
        $contractId = trim((string) $this->smaregiApi->getContractIdForProject($projectId));
        if ($contractId === '') {
            throw new InvalidArgumentException('スマレジ連携が設定されていません');
        }
        $query = ['limit' => 20];
        if (in_array(ExistingMemberLinkField::CUSTOMER_CODE, $fields, true)) {
            $query['customer_code'] = $input[ExistingMemberLinkField::CUSTOMER_CODE] ?? '';
        }
        if (in_array(ExistingMemberLinkField::EMAIL, $fields, true)) {
            $query['mail_address'] = $input[ExistingMemberLinkField::EMAIL] ?? '';
        }
        $result = $this->smaregiApi->getCustomers($contractId, $query);
        if (! is_array($result)) {
            throw new InvalidArgumentException('スマレジから会員を取得できませんでした');
        }
        $list = array_is_list($result) ? $result : ($result['customers'] ?? []);
        if (! is_array($list)) {
            throw new InvalidArgumentException('スマレジから会員を取得できませんでした');
        }

        return ['contract_id' => $contractId, 'customer' => $this->matcher->match(array_values(array_filter($list, 'is_array')), $fields, $input)];
    }

    /** @return array{linked: bool, message: string} */
    private function sendMail(int $projectId, ?User $user, string $contractId, string $customerId, string $email): array
    {
        if ($email === '') {
            throw new InvalidArgumentException('メールアドレスを入力してください');
        }
        $token = Str::random(48);
        ExistingMemberLinkRequest::query()->create([
            'project_id' => $projectId,
            'user_id' => $user?->id,
            'smaregi_customer_id' => $customerId,
            'contract_id' => $contractId,
            'email' => $email,
            'token' => $token,
            'expires_at' => now()->addMinutes(30),
        ]);
        Mail::to($email)->send(new ExistingMemberLinkMail(url('/existing-member-link/'.$token)));
        LogHelper::info('[Member] existing link mail sent', [
            'project_id' => $projectId,
            'user_id' => $user?->id,
        ]);

        return ['linked' => false, 'message' => '確認メールを送信しました。30分以内に、申請したブラウザで確認するか、メールのリンクをアプリに貼り付けて連携してください'];
    }
}
