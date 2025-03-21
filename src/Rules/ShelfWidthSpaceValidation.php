<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Rules;

use App\Models\Layer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ShelfWidthSpaceValidation implements ValidationRule
{
    protected $layerId;

    protected $request;

    public function __construct($layerId, $request)
    {
        $this->layerId = $layerId;
        $this->request = $request;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Obtém o segmento, a prateleira e a seção
        $layer = Layer::with(['product', 'segment.shelf.section'])->find($this->layerId);
        if (! $layer) {
            $fail('Layer inválido.');

            return;
        }
        $segment = $layer->segment;
        $shelf = $segment->shelf;

        // Calcula a largura total de todos os segmentos
        $totalWidth = 0;
        $lastWidth = 0;
        foreach ($shelf->segments as $seg) {
            $productWidth = $seg->layer->product->width;
            $quantity = $seg->id === $segment->id ? $value : $seg->layer->quantity;
            $spacing = $seg->layer->spacing ? (float) $seg->layer->spacing : 0;

            $totalWidth += ($productWidth * $quantity) + $spacing;
            $lastWidth = $productWidth;
        }

        $sectionWidth = $layer->segment->shelf->section->width - $lastWidth;

        if ($totalWidth > $sectionWidth) {
            $remainingWidth = $sectionWidth;

            // Subtrai a largura dos outros segmentos
            foreach ($shelf->segments as $seg) {
                if ($seg->id !== $segment->id) {
                    $width = ($seg->layer->product->width * $seg->layer->quantity);
                    $spacing = $seg->layer->spacing ? (float) $seg->layer->spacing : 0;
                    $remainingWidth -= ($width + $spacing);
                }
            }

            // Calcula quantidade máxima possível para este segmento
            $maxProducts = floor($remainingWidth / $layer->product->width);
            $fail("A quantidade máxima de produtos para esta camada é {$maxProducts}.");
        }
    }
}
