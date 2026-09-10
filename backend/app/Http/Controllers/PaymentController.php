<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Database\UniqueConstraintViolationException;

class PaymentController extends Controller
{
    public function store(
        Request $request,
        Organization $organization,
        Customer $customer
    ) {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['nullable', 'string'],
        ]);

        if ($customer->organization_id !== $organization->id) {
            abort(404);
        }

        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) {
            return response()->json([
                'message' => 'Idempotency-Key header is required.',
            ], 422);
        }

        try {
            $payment = Payment::create([
                'organization_id' => $organization->id,
                'customer_id' => $customer->id,
                'reference' => 'PAY-' . Str::upper(Str::random(12)),
                'amount' => $validated['amount'],
                'currency' => strtoupper($validated['currency']),
                'status' => 'pending',
                'description' => $validated['description'] ?? null,
                'idempotency_key' => $idempotencyKey,
            ]);

            return response()->json($payment, 201);
        } catch (UniqueConstraintViolationException $e) {
            $payment = Payment::where('organization_id', $organization->id)
                ->where('idempotency_key', $idempotencyKey)
                ->firstOrFail();

            if (
                (int) $payment->customer_id !== (int) $customer->id ||
                (int) $payment->amount !== (int) $validated['amount'] ||
                strtoupper($payment->currency) !== strtoupper($validated['currency'])
            ) {
                return response()->json([
                    'message' => 'Idempotency-Key was already used for a different payment request.',
                ], 409);
            }

            return response()->json($payment, 200);
        }
    }
}
