<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Models;

use Callcocam\Plannerate\Enums\StoreStatus;
use Callcocam\Raptor\Models\Tenant;
use Callcocam\Raptor\Models\Traits\HasAddresses;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasAddresses, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'code',
        'phone',
        'email',
        'status',
    ];

    protected $casts = [
        'status' => StoreStatus::class,
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', StoreStatus::PUBLISHED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', StoreStatus::DRAFT);
    }
}
