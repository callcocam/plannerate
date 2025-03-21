<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShelfResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'section_id' => $this->section_id,
            'height' => $this->height,
            'depth' => $this->depth,
            'shelf_qty' => $this->shelf_qty,
            'ordering' => $this->ordering,
            'position' => floatval($this->position),
            'status' => $this->status,
            'segments' => SegmentResource::collection($this->whenLoaded('segments')),
            'section' => new SectionResource($this->whenLoaded('section')),
        ];
    }
}
