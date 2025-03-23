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
use Callcocam\Plannerate\Models\Section;
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
            $shelf->update($validated);
            if ($segment = data_get($validated, 'segment')) {
                if ($newSegment = $shelf->segments()->create($segment)) {
                    if ($layer = data_get($segment, 'layer')) {
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

    // Atualiza a seção da prateleira
    $shelf->section_id = $validated['section_id'];
    $shelf->save();

    return response()->json([
        'message' => 'Prateleira movida com sucesso',
        'shelf' => $shelf
    ]);
}

}
