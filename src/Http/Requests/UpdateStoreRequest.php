<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Requests;

use Callcocam\Plannerate\Enums\StoreStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                Rule::unique('stores')->ignore($this->store),
                'alpha_dash',
            ],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:10',
                Rule::unique('stores')->ignore($this->store),
            ],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'email' => ['sometimes', 'required', 'email', 'max:255'],
            'status' => ['sometimes', 'required', new Enum(StoreStatus::class)],

            // Campos do endereço
            'address.street' => ['sometimes', 'required', 'string', 'max:255'],
            'address.number' => ['sometimes', 'required', 'string', 'max:20'],
            'address.complement' => ['nullable', 'string', 'max:255'],
            'address.district' => ['sometimes', 'required', 'string', 'max:255'],
            'address.city' => ['sometimes', 'required', 'string', 'max:255'],
            'address.state' => ['sometimes', 'required', 'string', 'size:2'],
            'address.zip_code' => ['sometimes', 'required', 'string', 'size:8'],
        ];
    }
}
