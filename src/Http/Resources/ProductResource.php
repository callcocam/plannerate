<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'ean' => $this->ean,
            'price' => $this->price,
            'width' => $this->width,
            'height' => $this->height,
            'depth' => $this->depth,
            'image_url' => $this->image_url,
            'stackable' => $this->stackable,
            'hangable' => $this->hangable,
            'image' => $this->image?->url,
            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
            ],
            'status' => $this->status,
        ];
    }
}
