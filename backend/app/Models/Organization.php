<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Membership;
use App\Models\Customer;
use App\Models\Payment;

class Organization extends Model
{
    protected $fillable = [
        'name',
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
