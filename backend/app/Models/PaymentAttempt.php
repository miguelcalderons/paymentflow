<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttempt extends Model
{
    protected $fillable = ['payment_id', 'provider', 'provider_reference', 'status', 'failure_reason',];
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
