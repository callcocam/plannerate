<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Models;

use App\Core\Concerns\Sluggable\HasSlug;
use App\Core\Concerns\Sluggable\SlugOptions;
use App\Core\Landlord\BelongsToTenants;
use App\Models\User;
use Callcocam\Plannerate\Enums\SectionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends Model
{
    use BelongsToTenants,   HasFactory, HasSlug, HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'gondola_id',
        'name',
        'slug',
        'width', // largura da seção
        'ordering', // ordem da seção
        'status', // status da seção
        'shelf_height', // altura das prateleiras
        'hole_spacing', // distância entre os furos da prateleira
    ];

    protected $casts = [
        'height' => 'decimal:2',
        'width' => 'decimal:2',
        'position' => 'integer',
        'status' => SectionStatus::class,
    ];

    protected $appends = ['height'];

    public function gondola(): BelongsTo
    {
        return $this->belongsTo(Gondola::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getHeightAttribute(): float
    {
        return $this->gondola->height;
    }

    public function shelves(): HasMany
    {
        return $this->hasMany(Shelf::class);
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
