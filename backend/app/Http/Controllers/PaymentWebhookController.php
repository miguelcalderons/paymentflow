<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\WebhookEvent;
use App\PaymentStatus;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendPaymentWebhookJob;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $signature = $request->header('X-Mock-Signature');

        $expectedSignature = hash_hmac(
            'sha256',
            $request->getContent(),
            config('services.mock_payment.webhook_secret')
        );

        if (!$signature || !hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'message' => 'Invalid webhook signature.',
            ], 401);
        }

        $validated = $request->validate([
            'event_id' => ['required', 'string'],
            'type' => ['required', 'string'],
            'data' => ['required', 'array'],
            'data.payment_reference' => ['required', 'string'],
        ]);

        try {
            $result = DB::transaction(function () use ($request, $validated) {
                $event = WebhookEvent::create([
                    'provider' => 'mock',
                    'event_id' => $validated['event_id'],
                    'type' => $validated['type'],
                    'payload' => $request->all(),
                ]);

                $payment = null;

                if (in_array($validated['type'], [
                    'payment.succeeded',
                    'payment.failed',
                ], true)) {
                    $payment = Payment::where(
                        'reference',
                        $validated['data']['payment_reference']
                    )->firstOrFail();

                    if ($validated['type'] === 'payment.succeeded') {
                        if ($payment->status === PaymentStatus::Processing) {
                            $payment->transitionTo(PaymentStatus::Succeeded);
                        } elseif ($payment->status !== PaymentStatus::Succeeded) {
                            throw new DomainException(
                                'Payment cannot be marked as succeeded from its current status.'
                            );
                        }
                    }

                    if ($validated['type'] === 'payment.failed') {
                        if ($payment->status === PaymentStatus::Processing) {
                            $payment->transitionTo(PaymentStatus::Failed);
                        } elseif ($payment->status !== PaymentStatus::Failed) {
                            throw new DomainException(
                                'Payment cannot be marked as failed from its current status.'
                            );
                        }
                    }
                }

                $event->update([
                    'processed_at' => now(),
                ]);

                return [
                    'event' => $event,
                    'payment' => $payment,
                ];
            });
        } catch (UniqueConstraintViolationException $e) {
            return response()->json([
                'status' => 'duplicate',
            ], 200);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);
        }

        $payment = $result['payment'];

        if (
            $payment &&
            $payment->organization->webhook_url &&
            $payment->organization->webhook_secret
        ) {
            SendPaymentWebhookJob::dispatch($payment);
        }

        return response()->json([
            'status' => 'received',
            'event_id' => $result['event']->event_id,
        ], 200);
    }
}
