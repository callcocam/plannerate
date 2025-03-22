<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Models;
 
use Callcocam\Plannerate\Enums\GondolaStatus;
use Callcocam\Raptor\Core\Concerns\Sluggable\HasSlug;
use Callcocam\Raptor\Core\Concerns\Sluggable\SlugOptions;
use Callcocam\Raptor\Core\Landlord\BelongsToTenants;
use Callcocam\Raptor\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gondola extends Model
{
    use BelongsToTenants, HasFactory, HasSlug, HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'height',
        'width',
        'base_height',
        'thickness',
        'scale_factor',
        'location',
        'status',
    ];

    protected $casts = [
        'height' => 'int',
        'width' => 'int',
        'base_height' => 'int',
        'scale_factor' => 'int',
        'status' => GondolaStatus::class,
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('ordering');
    }

    public function scopePublished($query)
    {
        return $query->where('status', GondolaStatus::PUBLISHED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', GondolaStatus::DRAFT);
    }

    /**
     * @return SlugOptions
     */
    public function getSlugOptions()
    {
        if (is_string($this->slugTo())) {
            return SlugOptions::create()
                ->generateSlugsFrom($this->slugFrom())
                ->saveSlugsTo($this->slugTo());
        }
    }
}
