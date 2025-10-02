<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\Product; // Productモデルをインポート
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use App\Models\Order;

class StripeController extends Controller
{
    /**
     * Create a new Stripe Checkout Session for a single product.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\RedirectResponse
     */
    // 引数をRequestからProductモデルに変更
    public function checkout(Product $product)
    {
        // Stripeのシークレットキーをconfig()関数を使って設定
        // config/services.php からキーを取得します。
        Stripe::setApiKey(config('services.stripe.secret'));
        
        try {
            // Stripe Checkout Sessionを作成
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'jpy',
                        'product_data' => [
                            'name' => $product->name,
                        ],
                        'unit_amount' => $product->price,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                // 成功時とキャンセル時のリダイレクト先を指定
                'success_url' => route('checkout.success'),
                'cancel_url' => route('checkout.cancel'),
            ]);

            // 成功した場合は、Stripeの決済ページにリダイレクト
            return redirect($session->url, 303);

        } catch (\Exception $e) {
            // エラーが発生した場合、ログに記録してユーザーにメッセージを表示
            Log::error('Failed to create Stripe Checkout Session.', ['error' => $e->getMessage()]);
            return back()->with('error', '決済処理中にエラーが発生しました。');
        }
    }

    /**
     * Handle Stripe webhook events to update order status.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function webhook(Request $request)
    {
        // Stripeのシークレットキーをconfig()関数を使って設定
        Stripe::setApiKey(config('services.stripe.secret'));
        $payload = @file_get_contents('php://input');
        $sigHeader = $request->header('Stripe-Signature');
        
        // Stripe Webhookのシークレットキーをconfig()関数を使って取得
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            // Webhookの署名検証
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (SignatureVerificationException $e) {
            // 署名検証失敗
            Log::error('Webhook signature verification failed.', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        switch ($event->type) {
            // 決済が完了した際のイベントを処理
            case 'checkout.session.completed':
                $session = $event->data->object;
                Log::info('Checkout session completed', ['session' => $session]);

                // ここでデータベースの注文ステータスを更新する処理を記述
                // 例: $order = Order::where('stripe_session_id', $session->id)->firstOrFail();
                //     $order->payment_status = 'paid';
                //     $order->save();

                break;
            
            // その他のイベントタイプは必要に応じて追加
            default:
                Log::warning('Unhandled event type: ' . $event->type);
        }

        // 成功した場合は200 OKを返す
        return response('Webhook received', 200);
    }
}
