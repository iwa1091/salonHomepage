<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Models\Order;
use Carbon\Carbon;

class StripeController extends Controller
{
    /**
     * ✅ Stripe Checkout セッション作成
     */
    public function checkout(Request $request, Product $product)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        if ($product->stock <= 0) {
            return back()->with('error', 'この商品は在庫切れです。');
        }

        try {
            $user = $request->user();

            // ✅ ① まず仮注文を作成
            $order = Order::create([
                'user_id'        => $user->id,
                'product_id'     => $product->id,
                'order_number'   => strtoupper(uniqid('ORD')),
                'amount'         => $product->price,
                'currency'       => 'JPY',
                'payment_status' => 'pending',
                'ordered_at'     => Carbon::now(),
            ]);

            // ✅ ② Stripe セッション作成（ここで order_id を metadata に入れる）
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'jpy',
                        'product_data' => [
                            'name' => $product->name,
                            'description' => $product->description,
                        ],
                        'unit_amount' => intval($product->price),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('checkout.cancel'),

                'metadata' => [
                    'order_id'     => $order->id,  // ← ✅ ここで使用する
                    'user_id'      => $user->id,
                    'user_email'   => $user->email,
                    'product_id'   => $product->id,
                ],
            ]);

            // ✅ ③ Stripe Session の ID を保存
            $order->update([
                'stripe_session_id' => $session->id,
            ]);

            return redirect($session->url, 303);

        } catch (\Exception $e) {
            Log::error('Stripe Checkout Error', [
                'user_id'    => $request->user()->id ?? null,
                'product_id' => $product->id,
                'error'      => $e->getMessage()
            ]);

            return back()->with('error', '決済ページを開けませんでした。');
        }
    }
}
