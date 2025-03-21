<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Requests\Gondola;

use Callcocam\Plannerate\Enums\GondolaStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateGondolaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', Rule::unique('gondolas')->ignore($this->gondola), 'alpha_dash'],
            'height' => ['sometimes', 'required', 'numeric', 'min:0'],
            'width' => ['sometimes', 'required', 'numeric', 'min:0'],
            'base_height' => ['sometimes', 'required', 'numeric', 'min:0'],
            'location' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', new Enum(GondolaStatus::class)],
            'thickness' => ['sometimes', 'required', 'numeric', 'min:1'],
            'scale_factor' => ['sometimes', 'required', 'numeric', 'min:1'],
        ];
    }
}
