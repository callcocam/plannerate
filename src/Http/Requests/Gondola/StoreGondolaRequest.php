<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Requests\Gondola;

use Callcocam\Plannerate\Enums\GondolaStatus;
use Callcocam\Plannerate\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreGondolaRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'ulid', 'exists:tenants,id'],
            'user_id' => ['required', 'ulid', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'unique:gondolas', 'alpha_dash'],
            'height' => ['required', 'numeric', 'min:0'],
            'width' => ['required', 'numeric', 'min:0'],
            'base_height' => ['required', 'numeric', 'min:0'],
            'location' => ['required', 'string', 'max:255'],
            'status' => ['required', new Enum(GondolaStatus::class)],
            'thickness' => ['required', 'numeric', 'min:1'],
            'scale_factor' => ['required', 'numeric', 'min:1'],
        ];
    }
}
