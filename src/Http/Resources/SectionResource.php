<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'width' => $this->width,
            'height' => $this->height,
            'hole_spacing' => $this->hole_spacing, // espessura entre os furos
            'shelf_height' => $this->shelf_height, // espessura da prateleira
            'ordering' => $this->ordering,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],
            'shelves' => ShelfResource::collection($this->whenLoaded('shelves')),
        ];
    }
}
