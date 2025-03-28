<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Segment extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'shelf_id',
        'width',
        'ordering',
        'position',
        'quantity',
        'spacing',
        'status',
    ];

    public function layer()
    {
        return $this->hasOne(Layer::class)->orderBy('ordering');
    }

    public function shelf()
    {
        return $this->belongsTo(Shelf::class);
    }
}
