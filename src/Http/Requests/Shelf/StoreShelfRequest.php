<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Requests\Shelf;

use Callcocam\Plannerate\Enums\ShelfStatus;
use Callcocam\Plannerate\Http\Requests\BaseFormRequest;
use Callcocam\Plannerate\Models\Product;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreShelfRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'ulid', 'exists:tenants,id'],
            'section_id' => ['required', 'ulid', 'exists:sections,id'],
            'height' => ['required', 'numeric', 'min:0'],
            'depth' => ['required', 'numeric', 'min:0'],
            'ordering' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::enum(ShelfStatus::class)],
            'product_id' => ['nullable', 'ulid', 'exists:products,id'] // Para validação opcional de produto
        ];
    }
    
    /**
     * Handle a passed validation attempt.
     *
     * @return void
     */
    protected function passedValidation()
    {
        // Se um produto foi informado, validar se cabe na altura da prateleira
        if ($this->has('product_id') && $this->product_id) {
            $product = Product::findOrFail($this->product_id);
            
            // Verificar altura
            if ($product->height > $this->height) {
                throw ValidationException::withMessages([
                    'product_id' => "O produto é muito alto para esta prateleira. Altura do produto: {$product->height}cm, altura da prateleira: {$this->height}cm."
                ]);
            }
        }
    }
}
