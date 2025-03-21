<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Requests\Section;

use Callcocam\Plannerate\Enums\SectionStatus;
use Callcocam\Plannerate\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateSectionRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', Rule::unique('sections')->ignore($this->section), 'alpha_dash'],
            'width' => ['sometimes', 'numeric', 'min:0'],
            'ordering' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', new Enum(SectionStatus::class)],
            'shelf_height' => ['sometimes', 'numeric', 'min:0'],
            'hole_spacing' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
