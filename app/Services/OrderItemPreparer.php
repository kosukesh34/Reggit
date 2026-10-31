<?php

namespace App\Services;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Domain\Order\Policies\ProductAvailabilityPolicy;
use App\Domain\Order\Policies\ProductOptionSelectionPolicy;
use App\Domain\Order\Repositories\ProductCatalogRepository;
use App\Domain\Order\ValueObjects\OrderLineItem;

/** 注文明細の入力を検証し、販売可否・オプション価格を反映した明細にする。 */
class OrderItemPreparer
{
    public function __construct(
        private ProductCatalogRepository $productCatalogRepository,
        private ProductAvailabilityPolicy $productAvailabilityPolicy,
        private ProductOptionSelectionPolicy $productOptionSelectionPolicy,
    ) {}

    public function validateAndPrepareOrderItems(array $items, int $projectId, bool $ticketOrder = false): array
    {
        $orderItems = [];

        foreach ($items as $item) {
            $product = $this->productCatalogRepository->findForOrderOrFail((int) $item['product_id'], $projectId);
            $quantity = (int) $item['quantity'];
            if ($product->isSubscription) {
                throw new OrderOperationException('サブスクは回数券の画面から申し込んでください');
            }
            if ($product->isTicket !== $ticketOrder) {
                throw new OrderOperationException($ticketOrder
                    ? '回数券以外の商品は一緒に購入できません'
                    : '回数券は回数券の画面から購入してください');
            }

            $this->productAvailabilityPolicy->assertAvailable(
                $product->name,
                $product->isAvailable,
                $product->stock,
                $quantity,
            );

            [$optionsDelta, $optionsSummary] = $this->productOptionSelectionPolicy->resolve(
                $product->name,
                $product->optionGroups,
                $item['option_ids'] ?? [],
            );

            $unitPrice = $product->price + $optionsDelta;
            $note = $item['notes'] ?? null;
            if ($optionsSummary !== '') {
                $note = $note ? $optionsSummary.' / '.$note : $optionsSummary;
            }

            $orderItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'price' => $unitPrice,
                'notes' => $note,
                'option_ids' => array_values(array_unique(array_map('intval', $item['option_ids'] ?? []))),
            ];
        }

        return $orderItems;
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, price: float, notes?: string|null, product_name?: string|null}>  $orderItems
     * @return list<OrderLineItem>
     */
    public function toOrderLineItems(array $orderItems): array
    {
        $lineItems = [];
        foreach ($orderItems as $item) {
            $lineItems[] = new OrderLineItem(
                (int) $item['product_id'],
                (int) $item['quantity'],
                (float) $item['price'],
                $item['notes'] ?? null,
                isset($item['product_name']) ? (string) $item['product_name'] : null,
                $item['option_ids'] ?? [],
            );
        }

        return $lineItems;
    }
}
