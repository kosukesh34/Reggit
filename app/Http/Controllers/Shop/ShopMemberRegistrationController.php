<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\ProfileValidationRules;
use App\Services\Member\MemberDataReader;
use App\Services\MemberProfileRules;
use App\Support\ShopMemberSession;
use App\UseCases\Member\Data\MemberRegisterInput;
use App\UseCases\Member\MemberRegisterUseCase;
use App\Util\AdminContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/** Web で作成したユーザーの会員登録（アプリ・LIFF と同じ必須項目と検証）。 */
class ShopMemberRegistrationController extends Controller
{
    public function __construct(
        private ShopMemberSession $session,
        private MemberDataReader $memberData,
        private MemberProfileRules $profileRules,
        private MemberRegisterUseCase $registerMember,
    ) {}

    public function form(Request $request): View|RedirectResponse
    {
        $user = $this->session->current();
        if ($user === null || $user->member()->exists()) {
            return redirect()->to($this->route('shop.show', $request));
        }
        $data = $this->memberData->getMemberData($user, false);

        return view('frontend.shop-member-register', [
            'projectName' => (string) (AdminContext::getCurrentProject()?->name ?? ''),
            'fields' => is_array($data['profile_field_settings'] ?? null) ? $data['profile_field_settings'] : [],
            'user' => $user,
            'submitUrl' => $this->route('shop.member.register.store', $request),
            'logoutUrl' => $this->route('shop.logout', $request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->session->current();
        if ($user === null || $user->member()->exists()) {
            return redirect()->to($this->route('shop.show', $request));
        }
        $validated = $request->validate(
            $this->profileRules->getProfileValidationRules((int) $user->project_id),
            ProfileValidationRules::messages(),
        );
        $validated = $this->withVerifiedEmail($validated, $user);

        try {
            $this->registerMember->handle(new MemberRegisterInput($user, $validated));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['register' => $exception->getMessage()])->withInput();
        }

        return redirect()->to($this->route('shop.show', $request))->with('success', '会員登録が完了しました。');
    }

    /**
     * 登録処理は users.email を上書きするため、確認済みのメールを差し替えられないようにする。
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function withVerifiedEmail(array $validated, User $user): array
    {
        if ($user->email_verified_at !== null && is_string($user->email) && $user->email !== '') {
            $validated['email'] = $user->email;
        }

        return $validated;
    }

    private function route(string $name, Request $request): string
    {
        $projectKey = $request->route('project_key');
        if (is_string($projectKey) && $projectKey !== '') {
            return route($name, ['project_key' => $projectKey]);
        }

        return route(str_replace('shop.', 'shop.domain.', $name));
    }
}
