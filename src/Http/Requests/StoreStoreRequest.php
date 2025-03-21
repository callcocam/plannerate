<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Requests;

use Callcocam\Plannerate\Enums\StoreStatus;
use Illuminate\Validation\Rules\Enum;

class StoreStoreRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'ulid', 'exists:tenants,id'],
            'user_id' => ['required', 'ulid', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'unique:stores', 'alpha_dash'],
            'code' => ['required', 'string', 'max:10', 'unique:stores'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'status' => ['required', new Enum(StoreStatus::class)],

            // Campos do endereço
            'address.street' => ['required', 'string', 'max:255'],
            'address.number' => ['required', 'string', 'max:20'],
            'address.complement' => ['nullable', 'string', 'max:255'],
            'address.district' => ['required', 'string', 'max:255'],
            'address.city' => ['required', 'string', 'max:255'],
            'address.state' => ['required', 'string', 'size:2'],
            'address.zip_code' => ['required', 'string', 'size:8'],
        ];
    }
}
