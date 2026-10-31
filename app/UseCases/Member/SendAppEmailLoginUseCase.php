<?php

namespace App\UseCases\Member;

use App\Mail\ShopMagicLinkMail;
use App\Models\User;
use App\Services\Member\MemberTransferCodeIssuer;
use App\Util\AdminContext;
use Illuminate\Support\Facades\Mail;

/** アプリのメールログイン。メール確認済みの会員にだけ、引き継ぎコード入りのリンクを送る。 */
class SendAppEmailLoginUseCase
{
    public function __construct(
        private MemberTransferCodeIssuer $transferCodes,
    ) {}

    /** 登録の有無が分からないよう、該当しないときも何も返さない。 */
    public function handle(string $email): void
    {
        $project = AdminContext::getCurrentProject();
        abort_unless($project !== null, 404);
        $projectId = (int) $project->id;
        $email = strtolower(trim($email));

        // 確認していないプロフィール上のメールではログインさせない
        $user = User::query()
            ->where('project_id', $projectId)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNotNull('email_verified_at')
            ->whereHas('member', fn ($query) => $query->where('project_id', $projectId))
            ->orderBy('id')
            ->first();
        if ($user === null) {
            return;
        }

        $issued = $this->transferCodes->issue($user, null);
        $url = route('app-login.open', [
            'project_key' => (string) $project->project_key,
            'member_id' => $issued->memberId,
            'code' => $issued->code,
        ]);
        Mail::to((string) $user->email)->send(new ShopMagicLinkMail($url, (string) $project->name));
    }
}
