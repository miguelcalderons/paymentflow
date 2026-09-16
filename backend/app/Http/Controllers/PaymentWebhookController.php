<?php

namespace App\Http\Controllers;

use App\Jobs\SendPaymentWebhookJob;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\PaymentStatus;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('Provider webhook received', [
            'provider' => 'mock',
            'event_id' => $request->input('event_id'),
            'type' => $request->input('type'),
            'payment_reference' => $request->input('data.payment_reference'),
        ]);

        $signature = $request->header('X-Mock-Signature');

        $expectedSignature = hash_hmac(
            'sha256',
            $request->getContent(),
            config('services.mock_payment.webhook_secret')
        );

        if (!$signature || !hash_equals($expectedSignature, $signature)) {
            Log::warning('Provider webhook signature invalid', [
                'provider' => 'mock',
                'event_id' => $request->input('event_id'),
                'payment_reference' => $request->input('data.payment_reference'),
            ]);

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
            Log::info('Duplicate provider webhook ignored', [
                'provider' => 'mock',
                'event_id' => $validated['event_id'],
                'type' => $validated['type'],
                'payment_reference' => $validated['data']['payment_reference'],
            ]);

            return response()->json([
                'status' => 'duplicate',
            ], 200);
        } catch (DomainException $e) {
            Log::warning('Provider webhook rejected by payment state', [
                'provider' => 'mock',
                'event_id' => $validated['event_id'],
                'type' => $validated['type'],
                'payment_reference' => $validated['data']['payment_reference'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 409);
        }

        $payment = $result['payment'];

        /*
         * At this point the database transaction has committed successfully.
         */
        Log::info('Provider webhook processed', [
            'provider' => 'mock',
            'event_id' => $validated['event_id'],
            'type' => $validated['type'],
            'payment_id' => $payment?->id,
            'payment_reference' => $payment?->reference,
            'status' => $payment?->status?->value,
        ]);

        if (
            $payment &&
            $payment->organization->webhook_url &&
            $payment->organization->webhook_secret
        ) {
            SendPaymentWebhookJob::dispatch($payment);

            Log::info('Outbound payment webhook queued', [
                'payment_id' => $payment->id,
                'payment_reference' => $payment->reference,
                'organization_id' => $payment->organization_id,
                'status' => $payment->status->value,
            ]);
        }

        return response()->json([
            'status' => 'received',
            'event_id' => $result['event']->event_id,
        ], 200);
    }
}
