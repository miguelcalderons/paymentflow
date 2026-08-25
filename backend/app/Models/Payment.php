<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\PaymentStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transitionTo(PaymentStatus $status): void
    {
        if (! $this->status->canTransitionTo($status)) {
            throw new \DomainException(
                "Cannot transition payment from {$this->status->value} to {$status->value}"
            );
        }

        $this->update([
            'status' => $status,
        ]);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }
}
