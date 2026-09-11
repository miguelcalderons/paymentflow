<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationApiKey extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'key_hash',
        'last_used_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
