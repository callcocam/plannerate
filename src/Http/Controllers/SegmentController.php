<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Controllers;

use App\Http\Controllers\Controller;
use Callcocam\Plannerate\Http\Requests\Segment\StoreSegmentRequest;
use Callcocam\Plannerate\Http\Requests\Segment\UpdateSegmentRequest;
use Callcocam\Plannerate\Http\Resources\SegmentResource;
use Callcocam\Plannerate\Models\Layer;
use Callcocam\Plannerate\Models\Section;
use Callcocam\Plannerate\Models\Segment;
use Callcocam\Plannerate\Models\Shelf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * SegmentController
 * 
 * Controlador responsável pelo gerenciamento de segmentos em prateleiras.
 * Um segmento representa uma divisão na prateleira onde produtos são posicionados.
 */
class SegmentController extends Controller
{
    /**
     * Armazena um novo segmento em uma prateleira
     * 
     * @param StoreSegmentRequest $request Requisição validada com dados do segmento
     * @param Shelf $shelf Prateleira onde o segmento será criado
     * @return \Illuminate\Http\JsonResponse Recurso do segmento criado
     */
    public function store(StoreSegmentRequest $request, Shelf $shelf)
    {
        DB::beginTransaction();
        try {
            $segment = $shelf->segments()->create($request->validated());
            $segment->layer()->create($request->product);

            DB::commit();
            return response()->json([
                'data' => new SegmentResource($segment->load('layer', 'layer.product')),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro ao criar segmento: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Atualiza um segmento existente
     * 
     * @param UpdateSegmentRequest $request Requisição validada com dados do segmento
     * @param Segment $segment Segmento a ser atualizado
     * @return \Illuminate\Http\RedirectResponse Redirecionamento com mensagem de sucesso ou erro
     */
    public function update(UpdateSegmentRequest $request, Segment $segment)
    {
        DB::beginTransaction();
        try {
            $segment->update($request->validated());

            $layer = data_get($request->validated(), 'layer');
            if ($layer) {
                $segment->layer()->update($layer);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Segmento de produto atualizado com sucesso');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erro ao atualizar segmento de produto: ' . $e->getMessage());
        }
    }

    /**
     * Remove um segmento e sua camada associada
     * 
     * @param Segment $segment Segmento a ser removido
     * @return \Illuminate\Http\JsonResponse Mensagem de sucesso ou erro
     */
    public function destroy(Segment $segment)
    {
        DB::beginTransaction();
        try {
            $segment->layer()->delete();
            $segment->delete();

            DB::commit();
            return response()->json(['message' => 'Segmento de produto removido com sucesso :)']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro ao remover segmento de produto :('], 500);
        }
    }

    /**
     * Atualiza a prateleira de um segmento
     * 
     * @param Request $request Requisição com dados da nova prateleira
     * @param Segment $segment Segmento a ser movido
     * @return \Illuminate\Http\RedirectResponse Redirecionamento com mensagem de sucesso ou erro
     */
    public function shelfUpdate(Request $request, Segment $segment)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'toShelfId' => 'required|exists:shelves,id',
                'segmentId' => 'required|exists:sections,id',
            ]);

            $section = Section::find($request->segmentId);
            if (!$section) {
                return redirect()->back()->with('error', 'Seção não encontrada');
            }

            $shelf = Shelf::findOrFail($request->toShelfId);
            if ($shelf->id === $segment->shelf_id) {
                return redirect()->back()->with('error', 'O segmento já está na prateleira selecionada');
            }

            $errorMessage = $this->validateShelfCapacity($shelf, $segment, $request->layerId);
            if ($errorMessage) {
                return redirect()->back()->with('error', $errorMessage);
            }

            $quantity = $shelf->segments()->count() + 1;
            $segment->update([
                'shelf_id' => $request->toShelfId,
                'ordering' => $quantity
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Segmento de produto atualizado com sucesso');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erro ao atualizar segmento de produto: ' . $e->getMessage());
        }
    }

    /**
     * Valida se há espaço suficiente na prateleira para o segmento
     * 
     * @param Shelf $shelf Prateleira de destino
     * @param Segment $segment Segmento a ser validado
     * @param int|null $layerId ID da camada (opcional)
     * @return string|null Mensagem de erro ou null se válido
     */
    protected function validateShelfCapacity(Shelf $shelf, Segment $segment, $layerId = null)
    {
        if ($shelf->segments()->count() === 0) {
            return null;
        }

        // Calcula a largura total ocupada por todos os segmentos na prateleira
        $totalWidth = 0;
        $lastWidth = 0;

        foreach ($shelf->segments as $seg) {
            $productWidth = (float)$seg->layer->product->width;
            $quantity = $seg->id === $segment->id ? $segment->layer->quantity : $seg->layer->quantity;
            $spacing = $seg->layer->spacing ? (float) $seg->layer->spacing : 0;

            $totalWidth += ($productWidth * $quantity) + $spacing;
            $lastWidth = $productWidth;
        }

        // Adiciona a largura do novo segmento/camada se fornecido
        if ($layerId) {
            $layer = Layer::find($layerId);
            if ($layer) {
                $productWidth = (float)$layer->product->width;
                $quantity = $segment->layer->quantity;
                $spacing = $layer->spacing ? (float) $layer->spacing : 0;
                $totalWidth += ($productWidth * $quantity) + $spacing;
            }
        }

        // Verifica se a largura total excede a largura disponível na seção
        $sectionWidth = $shelf->section->width - $lastWidth;
        if ($totalWidth > $sectionWidth) {
            return "A quantidade máxima de produtos para esta camada foi atingida.";
        }

        return null;
    }
}
