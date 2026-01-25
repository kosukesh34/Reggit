<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    private const POINTS_EARN_RATE = 100;

    public function createOrder(array $orderData, User $user): Order
    {
        DB::beginTransaction();
        try {
            $orderItems = $this->validateAndPrepareOrderItems($orderData['items']);
            $totalAmount = $this->calculateTotalAmount($orderItems);
            
            $pointsUsed = $orderData['points_used'] ?? 0;
            $finalAmount = $this->processPoints($user, $pointsUsed, $totalAmount);

            $order = $this->createOrderRecord($user, $finalAmount, $orderData, $pointsUsed);
            $this->createOrderItems($order, $orderItems);
            $this->updateProductStock($orderItems);
            $this->awardPoints($user, $order, $finalAmount);

            DB::commit();
            return $order->load('items.product');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function validateAndPrepareOrderItems(array $items): array
    {
        $orderItems = [];

        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);

            $this->validateProductAvailability($product, $item['quantity']);

            $orderItems[] = [
                'product' => $product,
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price' => $product->price,
                'notes' => $item['notes'] ?? null,
            ];
        }

        return $orderItems;
    }

    private function validateProductAvailability(Product $product, int $quantity): void
    {
        if (!$product->is_available) {
            throw new \Exception("商品 {$product->name} は現在利用できません");
        }

        if ($product->stock < $quantity) {
            throw new \Exception("商品 {$product->name} の在庫が不足しています");
        }
    }

    private function calculateTotalAmount(array $orderItems): float
    {
        $total = 0;
        foreach ($orderItems as $item) {
            $total += $item['product']->price * $item['quantity'];
        }
        return $total;
    }

    private function processPoints(User $user, int $pointsUsed, float $totalAmount): float
    {
        if ($pointsUsed <= 0) {
            return $totalAmount;
        }

        $member = $user->member;
        if (!$member) {
            throw new \Exception('会員登録が必要です');
        }

        if ($member->points < $pointsUsed) {
            throw new \Exception('ポイントが不足しています');
        }

        $member->usePoints($pointsUsed, '注文で使用', null);
        return max(0, $totalAmount - $pointsUsed);
    }

    private function createOrderRecord(User $user, float $finalAmount, array $orderData, int $pointsUsed): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'total_amount' => $finalAmount,
            'status' => 'pending',
            'payment_method' => $orderData['payment_method'],
            'points_used' => $pointsUsed,
            'points_earned' => 0,
            'notes' => $orderData['notes'] ?? null,
        ]);
    }

    private function createOrderItems(Order $order, array $orderItems): void
    {
        foreach ($orderItems as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'notes' => $item['notes'],
            ]);
        }
    }

    private function updateProductStock(array $orderItems): void
    {
        foreach ($orderItems as $item) {
            $product = $item['product'];
            $product->stock -= $item['quantity'];
            $product->save();
        }
    }

    private function awardPoints(User $user, Order $order, float $finalAmount): void
    {
        $member = $user->member;
        if (!$member || $finalAmount <= 0) {
            return;
        }

        $pointsEarned = floor($finalAmount / self::POINTS_EARN_RATE);
        if ($pointsEarned > 0) {
            $member->addPoints($pointsEarned, '購入で獲得', $order->id);
            $order->update(['points_earned' => $pointsEarned]);
        }
    }
}

