<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Controllers;
 
use App\Http\Controllers\Controller;
use Callcocam\Plannerate\Http\Requests\Section\StoreSectionRequest;
use Callcocam\Plannerate\Http\Requests\Section\UpdateSectionRequest;
use Callcocam\Plannerate\Models\Gondola;
use Callcocam\Plannerate\Models\Section;
use Callcocam\Plannerate\Services\ShelfPositioningService;
use Callcocam\Raptor\Core\Landlord\Facades\Landlord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSectionRequest $request)
    {
        $gondola = Gondola::find($request->gondola_id);
        $data = $request->validated();
        $tenantId = Landlord::getTenantId('tenant_id');
        $userId = auth()->id();
        Section::where('gondola_id', $gondola->id)->get()->map(function ($section) {
            $section->shelves()->forceDelete();
            $section->forceDelete();
        });
        // Se estiver apenas copiando uma seção existente
        if ($sourceSectionId = data_get($data, 'copy_from_section_id')) {
            return $this->copySection($gondola, $sourceSectionId, $tenantId, $userId);
        }
        // Criar novas seções com o assistente
        if ($modules = data_get($data, 'modulos')) {
            try {
                $shelfService = new ShelfPositioningService;
                for ($i = 0; $i < $modules; $i++) {
                    $section = $gondola->sections()->create([
                        'tenant_id' => $tenantId,
                        'user_id' => $userId,
                        'width' => data_get($data, 'width'),
                        'height' => data_get($data, 'height', $gondola->height),
                        'depth' => data_get($data, 'depth', 40),
                        'name' => 'Modulo '.($i + 1),
                        'ordering' => $i,
                        'status' => 'published',
                    ]);

                    if ($shelves = data_get($data, 'shelves')) {
                        // Coleta posições Y dos furos
                        $holePositions = collect($shelves)->pluck('position')->toArray();
                        // Calcula as posições das prateleiras
                        $shelfPositions = $shelfService->distributeShelvesEvenly($holePositions, data_get($data, 'shelfCount'));

                        // Cria as prateleiras nas posições calculadas
                        foreach ($shelfPositions as $index => $position) {
                            $section->shelves()->create([
                                'tenant_id' => $tenantId,
                                'user_id' => $userId,
                                'height' => data_get($data, 'shelf_height', 2),
                                'depth' => data_get($data, 'depth', 40),
                                'shelf_qty' => 0,
                                'ordering' => $index + 1,
                                'position' => round($position),
                                'status' => 'published',
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Erro ao criar as seções: '.$e->getMessage());
            }
        } else {
            // Criar uma única seção
            try {
                $section = $gondola->sections()->create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'width' => data_get($data, 'width'),
                    'height' => data_get($data, 'height', $gondola->height),
                    'depth' => data_get($data, 'depth', 40),
                    'name' => data_get($data, 'name', 'Modulo '.($gondola->sections()->count() + 1)),
                    'ordering' => $gondola->sections()->count(),
                    'status' => 'published',
                ]);

            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Erro ao criar a seção: '.$e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Seção criada com sucesso!');
    }

    /**
     * Copia uma seção completa com todas as suas prateleiras, segmentos, camadas e produtos
     */
    protected function copySection(Gondola $gondola, $sourceSectionId, $tenantId, $userId)
    {
        try {
            DB::beginTransaction();

            // Buscar a seção de origem com todos os relacionamentos
            $sourceSection = Section::with([
                'shelves.segments.layers.products', // Carrega toda a hierarquia
            ])->findOrFail($sourceSectionId);

            // Criar uma nova seção baseada na original
            $newSection = $gondola->sections()->create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'width' => $sourceSection->width,
                'height' => $sourceSection->height,
                'depth' => $sourceSection->depth,
                'name' => $sourceSection->name.' (Cópia)',
                'ordering' => $gondola->sections()->count(),
                'status' => 'published',
            ]);

            // Copiar todas as prateleiras e seus relacionamentos
            foreach ($sourceSection->shelves as $shelf) {
                $newShelf = $newSection->shelves()->create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'height' => $shelf->height,
                    'depth' => $shelf->depth,
                    'shelf_qty' => $shelf->shelf_qty,
                    'ordering' => $shelf->ordering,
                    'position' => $shelf->position,
                    'status' => 'published',
                ]);

                // Copiar os segmentos
                foreach ($shelf->segments as $segment) {
                    $newSegment = $newShelf->segments()->create([
                        'tenant_id' => $tenantId,
                        'user_id' => $userId,
                        'width' => $segment->width,
                        'height' => $segment->height,
                        'depth' => $segment->depth,
                        'ordering' => $segment->ordering,
                        'status' => 'published',
                    ]);

                    // Copiar as camadas
                    foreach ($segment->layers as $layer) {
                        $newLayer = $newSegment->layers()->create([
                            'tenant_id' => $tenantId,
                            'user_id' => $userId,
                            'height' => $layer->height,
                            'depth' => $layer->depth,
                            'width' => $layer->width,
                            'ordering' => $layer->ordering,
                            'status' => 'published',
                        ]);

                        // Copiar os produtos
                        foreach ($layer->products as $product) {
                            $newLayer->products()->create([
                                'tenant_id' => $tenantId,
                                'user_id' => $userId,
                                'product_id' => $product->product_id,
                                'quantity' => $product->quantity,
                                'facing' => $product->facing,
                                'ordering' => $product->ordering,
                                'status' => 'published',
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Seção copiada com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Erro ao copiar a seção: '.$e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Section $section)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Section $section)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSectionRequest $request, Section $section)
    {
        try {
            $data = $request->validated();

            $section->update($data);

            return redirect()->back()->with('success', 'Seção atualizada com sucesso!')->with('record', $section);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao atualizar a seção: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Section $section)
    {
        try {
            // Primeiro remova todos os relacionamentos
            foreach ($section->shelves as $shelf) {
                foreach ($shelf->segments as $segment) {
                    foreach ($segment->layers as $layer) {
                        $layer->products()->delete();
                    }
                    $segment->layers()->delete();
                }
                $shelf->segments()->delete();
            }
            $section->shelves()->delete();

            // Agora remova a seção
            $section->delete();

            return redirect()->back()->with('success', 'Seção excluída com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao excluir a seção: '.$e->getMessage());
        }
    }

    public function reorder(Request $request, Gondola $gondola)
    {
        $request->validate([
            'sections' => 'required|array',
            'sections.*.id' => 'required|exists:sections,id',
            'sections.*.ordering' => 'required|integer|min:0',
        ]);

        try {
            foreach ($request->sections as $sectionData) {
                Section::where('id', $sectionData['id'])
                    ->where('gondola_id', $gondola->id) // Garante que a Modulo pertence à gôndola
                    ->update(['ordering' => $sectionData['ordering']]);
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao atualizar a ordem das seções: '.$e->getMessage());
        }

        return redirect()->back()->with('success', 'Ordem das seções atualizada com sucesso!');
    }

    /**
     * Duplica uma seção existente
     */
    public function duplicate(Request $request, Section $section)
    {
        $tenantId = Landlord::getTenantId('tenant_id');
        $userId = auth()->id();

        return $this->copySection($section->gondola, $section->id, $tenantId, $userId);
    }
}
