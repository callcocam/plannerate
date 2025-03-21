<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Requests\Section;

use Callcocam\Plannerate\Enums\SectionStatus;
use Callcocam\Plannerate\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreSectionRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'ulid', 'exists:tenants,id'],
            'user_id' => ['required', 'ulid', 'exists:users,id'],
            'gondola_id' => ['required', 'ulid', 'exists:gondolas,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'unique:sections', 'alpha_dash'],
            'width' => ['required', 'numeric', 'min:0'],
            'height' => ['required', 'numeric', 'min:0'],
            'ordering' => ['nullable', 'integer', 'min:0'],
            'shelf_start' => ['nullable', 'string'],
            'status' => ['nullable', new Enum(SectionStatus::class)],
            'shelf_height' => ['nullable', 'numeric', 'min:0'],
            'hole_spacing' => ['nullable', 'numeric', 'min:0'],
            'modulos' => ['nullable', 'integer', 'min:0'],
            'shelves' => ['nullable', 'array'],
            'shelfCount' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
