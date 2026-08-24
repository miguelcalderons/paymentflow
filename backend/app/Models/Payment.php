<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Organization;
use App\Models\Customer;

class Payment extends Model
{
    protected $fillable = [
        'organization_id',
        'customer_id',
        'reference',
        'amount',
        'currency',
        'status',
        'description',
        'idempotency_key',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
