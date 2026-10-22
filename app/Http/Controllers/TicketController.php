<?php

namespace App\Http\Controllers;

use App\Domain\Settings\Repositories\FeatureSettings;
use App\Models\MemberTicket;
use App\Models\Product;
use App\Services\Ticket\MemberTicketService;
use App\Services\UserService;
use App\Util\AdminContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * 回数券の購入候補・保有一覧と利用。購入は通常の注文 API（order_type=ticket）を使う。
 */
class TicketController extends Controller
{
    public function __construct(
        private UserService $userService,
        private MemberTicketService $tickets,
        private FeatureSettings $features,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $member = $this->userService->getOrCreateUser($request)->member;
        $plans = $this->features->isFeatureEnabled('feature_tickets_enabled', $projectId)
            ? Product::query()->whereNotNull('ticket_uses')->where('is_available', true)->orderBy('price')->get()
                ->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => (int) $product->price,
                    'image_url' => $product->image_url,
                    'ticket_uses' => $product->ticket_uses,
                    'ticket_valid_days' => $product->ticket_valid_days,
                ])->all()
            : [];
        $tickets = $member === null ? [] : MemberTicket::query()
            ->where('member_id', $member->id)
            ->latest('id')
            ->get()
            ->map(fn (MemberTicket $ticket): array => $this->ticketArray($ticket))
            ->all();

        return response()->json([
            'plans' => $plans,
            'tickets' => $tickets,
            'requires_membership' => $member === null,
        ]);
    }

    public function use(Request $request, int $id): JsonResponse
    {
        try {
            $ticket = $this->tickets->useOne($this->userService->getOrCreateUser($request), $id);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? '回数券を使用できません',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true, 'data' => $this->ticketArray($ticket)]);
    }

    /** @return array<string, mixed> */
    private function ticketArray(MemberTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'name' => $ticket->name,
            'total_uses' => $ticket->total_uses,
            'used_count' => $ticket->used_count,
            'remaining_uses' => $ticket->remainingUses(),
            'expires_at' => $ticket->expires_at?->toIso8601String(),
            'status' => $ticket->status(),
            'purchased_at' => $ticket->created_at?->toIso8601String(),
        ];
    }
}
