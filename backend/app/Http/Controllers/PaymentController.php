<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

        $payment = Payment::create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'reference' => 'PAY-' . Str::upper(Str::random(12)),
            'amount' => $validated['amount'],
            'currency' => strtoupper($validated['currency']),
            'status' => 'pending',
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json($payment, 201);
    }
}
