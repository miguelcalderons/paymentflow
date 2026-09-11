<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'webhook_url',
        'webhook_secret',
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

    public function apiKeys(): HasMany
    {
        return $this->hasMany(OrganizationApiKey::class);
    }
}
