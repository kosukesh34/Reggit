<?php

namespace App\Http\Support;

use App\Models\Order;
use App\Services\UserService;
use App\UseCases\Order\Data\OrderOutput;
use App\UseCases\Order\Data\OrderStoreInput;
use App\UseCases\Order\OrderCancelUnpaidUseCase;
use App\UseCases\Order\OrderIndexUseCase;
use App\UseCases\Order\OrderShowUseCase;
use App\UseCases\Order\OrderStoreUseCase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class OrderRequestHandler
{
    public function __construct(
        private OrderIndexUseCase $indexUseCase,
        private OrderShowUseCase $showUseCase,
        private OrderStoreUseCase $storeUseCase,
        private OrderCancelUnpaidUseCase $cancelUnpaidUseCase,
        private UserService $userService,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->indexUseCase->handle($this->userService->getOrCreateUser($request), 20);
    }

    public function show(Request $request, int $id): Order
    {
        return $this->showUseCase->handle($this->userService->getOrCreateUser($request), $id);
    }

    /** @param array<string, mixed> $validated */
    public function store(Request $request, array $validated): OrderOutput
    {
        return $this->storeUseCase->handle(new OrderStoreInput(
            $this->userService->getOrCreateUser($request),
            $validated,
        ));
    }

    public function cancelUnpaid(Request $request, int $id): Order
    {
        return $this->cancelUnpaidUseCase->handle($this->userService->getOrCreateUser($request), $id);
    }
}
