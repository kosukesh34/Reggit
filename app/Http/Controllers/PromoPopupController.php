<?php

namespace App\Http\Controllers;

use App\Domain\Promotion\PromoPopupEvent;
use App\Domain\Promotion\ValueObjects\PromoPopupContent;
use App\Services\UserService;
use App\UseCases\Promotion\ListShowingPromoPopupsUseCase;
use App\UseCases\Promotion\RecordPromoPopupEventUseCase;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** 起動時に表示する宣伝ポップアップ（LIFF・ネイティブ共通）。表示相手と店舗はサーバーで絞る。 */
class PromoPopupController extends Controller
{
    public function index(Request $request, ListShowingPromoPopupsUseCase $useCase, UserService $users, StoreContext $stores): JsonResponse
    {
        $member = $users->findUser($request)?->member;
        $popups = $useCase->handle(AdminContext::requireCurrentProjectId(), $member !== null ? (int) $member->id : null, $stores->store?->id);

        return response()->json(['popups' => array_map(fn (PromoPopupContent $popup): array => [
            'id' => $popup->id,
            'title' => $popup->title,
            'body' => $popup->body,
            'image_url' => $popup->imageUrl,
            'display_type' => $popup->displayType,
            'button_label' => $popup->buttonLabel,
            'link_url' => $popup->linkUrl,
            'secondary_button_label' => $popup->secondaryButtonLabel,
            'secondary_link_url' => $popup->secondaryLinkUrl,
            'frequency' => $popup->frequency,
            'version' => $popup->version,
        ], $popups)]);
    }

    public function event(Request $request, int $id, RecordPromoPopupEventUseCase $useCase): JsonResponse
    {
        $event = (string) $request->validate(['event' => ['required', Rule::in(array_keys(PromoPopupEvent::getValues()))]])['event'];
        abort_unless($useCase->handle(AdminContext::requireCurrentProjectId(), $id, $event), 404);

        return response()->json(['success' => true]);
    }
}
