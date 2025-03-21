<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Requests\Product;

use Callcocam\Plannerate\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'required', 'ulid', 'exists:categories,id'],
            'image_id' => ['nullable', 'ulid', 'exists:images,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', Rule::unique('products')->ignore($this->product), 'alpha_dash'],
            'ean' => ['sometimes', 'required', 'string', 'size:13', Rule::unique('products')->ignore($this->product)],
            'width' => ['sometimes', 'required', 'numeric', 'min:0'],
            'height' => ['sometimes', 'required', 'numeric', 'min:0'],
            'depth' => ['sometimes', 'required', 'numeric', 'min:0'],
            'facing' => ['sometimes', 'required', 'integer', 'min:1'],
            'weight' => ['sometimes', 'required', 'numeric', 'min:0'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'stackable' => ['sometimes', 'required', 'boolean'],
            'perishable' => ['sometimes', 'required', 'boolean'],
            'flammable' => ['sometimes', 'required', 'boolean'],
            'hangable' => ['sometimes', 'required', 'boolean'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', new Enum(ProductStatus::class)],
        ];
    }
}
