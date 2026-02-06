<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Customer; // ★ 追加：顧客モデル
use App\Mail\AdminOrderNotificationMail;
use App\Mail\UserOrderConfirmationMail;
use Carbon\Carbon;

class StripeWebhookController extends Controller
{
    /**
     * ✅ Stripe から送信される Webhook を処理
     */
    public function handle(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        // Webhook Secret は config/services.php 経由で取得
        $secret    = config('services.stripe.webhook_secret');

        // ✅ 署名用シークレット未設定やヘッダ欠落をログで明示（原因切り分けしやすくする）
        if (empty($secret)) {
            Log::critical('❌ STRIPE_WEBHOOK_SECRET is empty. Check services.stripe.webhook_secret');
            return response('Webhook secret not configured', 500);
        }
        if (empty($sigHeader)) {
            Log::error('❌ Stripe-Signature header missing');
            return response('Signature header missing', 400);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            Log::error('❌ Invalid Stripe payload', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            Log::error('❌ Invalid Stripe signature', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        switch ($event->type) {

            case 'checkout.session.completed':
                $session = $event->data->object;
                Log::info('✅ Checkout completed Webhook received', ['session_id' => $session->id]);

                // ✅ 非同期決済などで paid じゃない可能性があるため、paid のみ確定処理にする
                if (($session->payment_status ?? null) !== 'paid') {
                    Log::info('ℹ️ checkout.session.completed but not paid', [
                        'session_id'      => $session->id,
                        'payment_status'  => $session->payment_status ?? null,
                    ]);
                    return response('Not paid yet', 200);
                }

                // 1. Stripe Session IDを使って既存の仮注文を検索する (重複作成防止)
                // ✅ pending 条件が厳しすぎると見つからないため、まず session_id だけで取得する
                $order = Order::where('stripe_session_id', $session->id)->first();

                if (!$order) {
                    // 注文が見つからない場合でも Stripe には 200 を返す（再送ループ抑止）
                    Log::warning('⚠️ Order not found.', ['session_id' => $session->id]);
                    return response('Order not found', 200);
                }

                // ✅ すでに paid なら冪等（同じWebhook再送でも二重処理しない）
                if (($order->payment_status ?? null) === 'paid') {
                    Log::info('ℹ️ Order already paid. Skip.', [
                        'order_id'   => $order->id,
                        'session_id' => $session->id,
                    ]);
                    return response('Already processed', 200);
                }

                $product = $order->product;

                if (!$product) {
                    Log::error('❌ Product not found for order.', ['order_id' => $order->id]);
                    // Stripe には 200 を返す運用が安全だが、既存挙動を崩さないため 404 のままにする
                    return response('Product not found for order', 404);
                }

                // ✅ customer_details が null の場合に備えてガード（nullsafe）
                $details = $session->customer_details ?? null;

                // 2. 注文ステータスを paid に更新
                $order->update([
                    'payment_status'    => 'paid',
                    'stripe_payment_id' => $session->payment_intent ?? null,
                    // 配送先情報をStripeセッション情報で上書き（または既存の値を維持）
                    'shipping_name'     => $details?->name ?? $order->shipping_name,
                    'shipping_address'  => $details?->address?->line1 ?? $order->shipping_address,
                    'shipping_phone'    => $details?->phone ?? $order->shipping_phone,
                    // amount と currency は仮注文作成時に設定済みのため更新は不要
                ]);
                Log::info('✅ Order status updated to PAID.', ['order_id' => $order->id]);

                // 3. ✅ 在庫引き落とし
                if ($product->stock > 0) {
                    $product->decrement('stock', 1);
                    Log::info('✅ Product stock decremented.', ['product_id' => $product->id]);
                }

                // 4. ✅ 顧客統計情報の更新（購入数・総支出・最終購入日）
                try {
                    $customer = null;

                    // 💡 優先：Order に customer リレーションがある場合
                    if (method_exists($order, 'customer') && $order->customer) {
                        $customer = $order->customer;
                    } else {
                        // Fallback：メールアドレスから Customer を検索
                        $emailFromSession = $details?->email ?? null;
                        $emailFromUser    = $order->user?->email ?? null;
                        $email            = $emailFromSession ?? $emailFromUser;

                        if ($email) {
                            $customer = Customer::where('email', $email)->first();
                        }
                    }

                    if ($customer && method_exists($customer, 'recalculateStats')) {
                        $customer->recalculateStats();
                        Log::info('👤 Customer stats recalculated.', [
                            'customer_id' => $customer->id,
                        ]);
                    } else {
                        Log::info('ℹ️ Customer not found or recalculateStats missing. Stats not updated.', [
                            'order_id' => $order->id,
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::error('❌ Failed to recalc customer stats', [
                        'order_id' => $order->id,
                        'error'    => $e->getMessage(),
                    ]);
                }

                // 5. ✅ メール送信 (アドレスの確実な取得とログの強化)
                try {
                    $adminEmail = env('MAIL_ADMIN_ADDRESS');

                    // ユーザーメールアドレスは、Stripeセッション または 紐づくユーザー情報から取得
                    $customerEmail = $details?->email ?? ($order->user?->email ?? null);

                    // 管理者メール
                    if ($adminEmail) {
                        Mail::to($adminEmail)->send(new AdminOrderNotificationMail($order));
                        Log::info('📧 Admin email scheduled.', ['to' => $adminEmail]);
                    } else {
                        Log::error('❌ MAIL_ADMIN_ADDRESS is not set. Admin email skipped.');
                    }

                    // ユーザーメール
                    if ($customerEmail) {
                        Mail::to($customerEmail)->send(new UserOrderConfirmationMail($order));
                        Log::info('📧 User confirmation email scheduled.', ['to' => $customerEmail]);
                    } else {
                        Log::warning('⚠️ Customer email not found. User confirmation email skipped.');
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Failed to send emails', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                break;

            default:
                Log::warning('⚠️ Unhandled Stripe event', ['type' => $event->type]);
        }

        return response('✅ Webhook processed', 200);
    }
}
