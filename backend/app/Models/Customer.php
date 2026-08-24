<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'email',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
    
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
