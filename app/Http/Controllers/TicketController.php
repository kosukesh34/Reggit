<?php

namespace App\Http\Controllers;

use App\Services\Ticket\MemberTicketService;
use App\Services\UserService;
use App\UseCases\Ticket\TicketIndexUseCase;
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
        private TicketIndexUseCase $index,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->index->handle(
            AdminContext::requireCurrentProjectId(),
            $this->userService->getOrCreateUser($request)->member,
        ));
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

        return response()->json(['success' => true, 'data' => $this->index->present($ticket)]);
    }
}
