<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        // テスト用: 認証がない場合は全注文を返す
        $userId = $request->header('X-Line-User-Id');
        if ($userId) {
            $user = \App\Models\User::where('line_user_id', $userId)->first();
            if ($user) {
                $orders = Order::where('user_id', $user->id)
                    ->with('items.product')
                    ->latest()
                    ->paginate(20);
                return response()->json($orders);
            }
        }
        
        // 認証ユーザーの場合
        $user = $request->user();
        if ($user) {
            $orders = Order::where('user_id', $user->id)
                ->with('items.product')
                ->latest()
                ->paginate(20);
            return response()->json($orders);
        }

        return response()->json(['error' => '認証が必要です'], 401);
    }

    public function show(Request $request, $id)
    {
        // テスト用: 認証がない場合
        $userId = $request->header('X-Line-User-Id');
        if ($userId) {
            $user = \App\Models\User::where('line_user_id', $userId)->first();
            if ($user) {
                $order = Order::where('user_id', $user->id)
                    ->with('items.product')
                    ->findOrFail($id);
                return response()->json($order);
            }
        }

        // 認証ユーザーの場合
        $user = $request->user();
        if ($user) {
            $order = Order::where('user_id', $user->id)
                ->with('items.product')
                ->findOrFail($id);
            return response()->json($order);
        }

        return response()->json(['error' => '認証が必要です'], 401);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,card,line_pay,points,stripe',
            'points_used' => 'integer|min:0',
            'notes' => 'nullable|string',
        ]);

        // テスト用: 認証がない場合はテストユーザーを使用
        $userId = $request->header('X-Line-User-Id');
        if ($userId) {
            $user = \App\Models\User::where('line_user_id', $userId)->first();
            if (!$user) {
                // テストユーザーを作成
                $user = \App\Models\User::create([
                    'line_user_id' => $userId,
                    'name' => 'Test User',
                ]);
            }
        } else {
            $user = $request->user();
        }

        if (!$user) {
            return response()->json(['error' => '認証が必要です'], 401);
        }

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            $orderItems = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                if (!$product->is_available) {
                    throw new \Exception("商品 {$product->name} は現在利用できません");
                }

                if ($product->stock < $item['quantity']) {
                    throw new \Exception("商品 {$product->name} の在庫が不足しています");
                }

                $itemTotal = $product->price * $item['quantity'];
                $totalAmount += $itemTotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'notes' => $item['notes'] ?? null,
                ];

                // 在庫を減らす
                $product->stock -= $item['quantity'];
                $product->save();
            }

            $pointsUsed = $request->points_used ?? 0;
            $member = $user->member;

            if ($pointsUsed > 0) {
                if (!$member) {
                    throw new \Exception('会員登録が必要です');
                }
                if ($member->points < $pointsUsed) {
                    throw new \Exception('ポイントが不足しています');
                }
                $member->usePoints($pointsUsed, '注文で使用', null);
            }

            $finalAmount = max(0, $totalAmount - $pointsUsed);

            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $finalAmount,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'points_used' => $pointsUsed,
                'points_earned' => 0,
                'notes' => $request->notes,
            ]);

            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'notes' => $item['notes'],
                ]);
            }

            // ポイント付与（100円につき1ポイント）
            if ($member && $finalAmount > 0) {
                $pointsEarned = floor($finalAmount / 100);
                if ($pointsEarned > 0) {
                    $member->addPoints($pointsEarned, '購入で獲得', $order->id);
                    $order->update(['points_earned' => $pointsEarned]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => '注文が完了しました',
                'order' => $order->load('items.product'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}

