<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Requests\Product;

use Callcocam\Plannerate\Enums\ProductStatus;
use Callcocam\Plannerate\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreProductRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'ulid', 'exists:tenants,id'],
            'user_id' => ['required', 'ulid', 'exists:users,id'],
            'category_id' => ['required', 'ulid', 'exists:categories,id'],
            'image_id' => ['nullable', 'ulid', 'exists:images,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'unique:products', 'alpha_dash'],
            'ean' => ['required', 'string', 'size:13', 'unique:products'],
            'width' => ['required', 'numeric', 'min:0'],
            'height' => ['required', 'numeric', 'min:0'],
            'depth' => ['required', 'numeric', 'min:0'],
            'facing' => ['required', 'integer', 'min:1'],
            'weight' => ['required', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'stackable' => ['required', 'boolean'],
            'perishable' => ['required', 'boolean'],
            'flammable' => ['required', 'boolean'],
            'hangable' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
            'status' => ['required', new Enum(ProductStatus::class)],
        ];
    }
}
