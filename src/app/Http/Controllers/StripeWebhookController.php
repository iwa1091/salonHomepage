<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    /**
     * Stripe Webhookからのリクエストを処理します。
     *
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request)
    {
        // Stripe Webhookの署名検証
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret'); // config()でシークレットを取得

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            // ペイロードが無効な場合の処理
            Log::error('Invalid payload', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            // 署名が無効な場合の処理
            Log::error('Invalid signature', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        // イベントタイプごとの処理
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                Log::info('Checkout session completed', ['session_id' => $session->id]);
                // TODO: ここに支払い成功時の注文ステータス更新処理などを記述
                break;
            
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                Log::info('Payment intent succeeded', ['payment_intent_id' => $paymentIntent->id]);
                // TODO: 支払い完了後の追加処理を記述
                break;
            
            // 必要に応じて他のイベントタイプを追加
            default:
                Log::warning('Unhandled event type', ['event_type' => $event->type]);
        }

        return response('Webhook received', 200);
    }
}
