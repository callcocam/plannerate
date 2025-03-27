<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Controllers;

use App\Http\Controllers\Controller;
use Callcocam\Plannerate\Http\Requests\Shelf\StoreShelfRequest;
use Callcocam\Plannerate\Http\Requests\Shelf\UpdateShelfRequest;
use Callcocam\Plannerate\Models\Layer;
use Callcocam\Plannerate\Models\Section;
use Callcocam\Plannerate\Models\Segment;
use Callcocam\Plannerate\Models\Shelf;
use Illuminate\Http\Request;

class ShelfController extends Controller
{
    public function store(StoreShelfRequest $request)
    {
        $data = $request->validated();
        $section = Section::find(data_get($data, 'section_id'));
        $quantity = $section->shelves()->count();
        $dataPosition = data_get($data, 'position', 0);
        if ($quantity > 0) {
            $position = $dataPosition ?? 40 * $quantity;
        } else {
            $position = $dataPosition;
        }
        $shelf = $section->shelves()->create([
            'user_id' => auth()->id(),
            'height' => $request->height ?? 4,
            'depth' => $request->depth ?? 40,
            'position' => $position,
            'ordering' => $section->shelves()->count(),
            'status' => 'published',
        ]);

        return redirect()->back()->with('success', 'Prateleira criada com sucesso')->with('record', $shelf);
    }

    public function update(UpdateShelfRequest $request, Shelf $shelf)
    {
        try {
            $validated = $request->validated();
            $segment = data_get($validated, 'segment');
            $layer = data_get($segment, 'layer');
            if ($segment && $layer) {
                if ($this->validateShelfCapacity($shelf, $segment, $layer)) {
                    return redirect()->back()->with('error', 'A quantidade máxima de produtos para esta camada foi atingida.');
                }
            }
            $shelf->update($validated);
            if ($segment) {
                if ($newSegment = $shelf->segments()->create($segment)) {
                    if ($layer) {
                        $newSegment->layer()->create($layer);
                    }
                }
            }
            return redirect()->back()->with('success', 'Prateleira atualizada com sucesso')->with('record', $shelf->load('segments', 'segments.layer', 'segments.layer.product', 'segments.layer.product.image'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao atualizar a prateleira: ' . $e->getMessage());
        }
    }

    public function destroy(Shelf $shelf)
    {
        try {
            $shelf->delete();

            return redirect()->back()->with('success', 'Prateleira excluída com sucesso');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao excluir a prateleira: ' . $e->getMessage());
        }
    }

    /**
     * Atualiza a seção de uma prateleira
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Shelf  $shelf
     * @return \Illuminate\Http\Response
     */
    public function updateSection(Request $request, Shelf $shelf)
    {
        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
        ]);

        try {
            // Atualiza a seção da prateleira
            $shelf->section_id = $validated['section_id'];

            $shelf->save();
            return redirect()->back()->with('success', 'Seção da prateleira atualizada com sucesso')->with('record', $shelf->load('section'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Seção não encontrada: ' . $e->getMessage());
        }
    }

    /**
     * Valida se há espaço suficiente na prateleira para o segmento
     * 
     * @param Shelf $shelf Prateleira de destino
     * @param array $segment Segmento a ser validado
     * @param int|null $layerId ID da camada (opcional)
     * @return string|null Mensagem de erro ou null se válido
     */
    protected function validateShelfCapacity(Shelf $shelf, array $segment, array $layer)
    {
        if ($shelf->segments()->count() === 0) {
            return null;
        }

        // Calcula a largura total ocupada por todos os segmentos na prateleira
        $totalWidth = 0;
        $lastWidth = 0;

        foreach ($shelf->segments as $seg) {
            $productWidth = (float)$seg->layer->product->width;
            $quantity = isset($seg['id']) && isset($segment['id']) && $seg['id'] === $segment['id']
                ? $segment['layer']['quantity']
                : $seg['layer']['quantity'];
            $spacing = isset($seg['layer']['spacing']) ? (float) $seg['layer']['spacing'] : 0;

            $totalWidth += ($productWidth * $quantity) + $spacing;
            $lastWidth = $productWidth;
        }

        // Adiciona a largura do novo segmento/camada se fornecido

        if ($layer) {
            $productWidth = (float)$layer['product']['width'];
            $quantity = $segment['layer']['quantity'];
            $spacing = isset($layer['spacing']) ? (float) $layer['spacing'] : 0;
            $totalWidth += ($productWidth * $quantity) + $spacing;
        }
        // Verifica se a largura total excede a largura disponível na seção
        $sectionWidth = $shelf->section->width - $lastWidth;
        if ($totalWidth > $sectionWidth) {
            return "A quantidade máxima de produtos para esta camada foi atingida.";
        }

        return null;
    }
}
