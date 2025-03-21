<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'segment_id' => $this->segment_id,
            'product_id' => $this->product_id,
            'height' => $this->height,
            'quantity' => $this->quantity,
            'spacing' => $this->spacing,
            'product' => new ProductResource($this->whenLoaded('product')),
            'segment' => new SegmentResource($this->whenLoaded('segment')),
        ];
    }
}
