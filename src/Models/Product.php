<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Models;

use App\Models\Tenant;
use App\Models\User;
use Callcocam\Plannerate\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'category_id',
        'image_id',
        'name',
        'slug',
        'ean',
        'width',
        'height',
        'depth',
        'facing',
        'weight',
        'price',
        'stackable',
        'perishable',
        'flammable',
        'hangable',
        'description',
        'status',
    ];

    protected $casts = [
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'depth' => 'decimal:2',
        'weight' => 'decimal:3',
        'price' => 'decimal:2',
        'facing' => 'integer',
        'stackable' => 'boolean',
        'perishable' => 'boolean',
        'flammable' => 'boolean',
        'hangable' => 'boolean',
        'status' => ProductStatus::class,
    ];

    protected $appends = ['image_url'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }

    public function getImageUrlAttribute()
    {
        return $this->image ? Storage::url($this->image->path) : null;
    }

    public function getVolumeAttribute(): float
    {
        return ($this->width * $this->height * $this->depth) / 1000; // Volume em litros
    }

    public function getDimensionsAttribute(): string
    {
        return "{$this->width}x{$this->height}x{$this->depth}cm";
    }

    public function scopePublished($query)
    {
        return $query->where('status', ProductStatus::PUBLISHED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', ProductStatus::DRAFT);
    }

    public function scopeStackable($query)
    {
        return $query->where('stackable', true);
    }

    public function scopePerishable($query)
    {
        return $query->where('perishable', true);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }
}
