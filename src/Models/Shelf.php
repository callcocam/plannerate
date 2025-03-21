<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Models;

use App\Core\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shelf extends Model
{
    use BelongsToTenants,  HasUlids, SoftDeletes;

    protected $fillable = [
        'section_id',
        'height',
        'depth',
        'shelf_qty',
        'ordering',
        'position',
        'status',
    ];

    protected $casts = [
        'height' => 'decimal:2',
        'depth' => 'decimal:2',
        'position' => 'decimal:2',
        'shelf_qty' => 'integer',
        'ordering' => 'integer',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function segments()
    {
        return $this->hasMany(Segment::class);
    }
}
