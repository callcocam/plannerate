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
use Callcocam\Plannerate\Models\Segment;
use Callcocam\Plannerate\Models\Shelf;

class SegmentController extends Controller
{
    public function store(StoreSegmentRequest $request, Shelf $shelf)
    {
        $segment = $shelf->segments()->create($request->validated());
        $segment->layer()->create($request->product);

        return response()->json([
            'data' => new SegmentResource($segment->load('layer', 'layer.product')),
        ], 201);
    }

    public function update(UpdateSegmentRequest $request, Segment $segment)
    {

        try {
            $segment->update($request->validated());
            if ($layer = data_get($request->validated(), 'layer')) {
                $segment->layer()->update($layer);
            }
            return redirect()->back()->with('success', 'Segmento de produto atualizado com sucesso');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao atualizar segmento de produto: ' . $e->getMessage());
        }
    }

    public function destroy(Segment $segment)
    {
        try {
            $segment->layer()->delete();
            $segment->delete();

            return response()->json(['message' => 'Segmento de produto removido com sucesso :)']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao remover segmento de produto :('], 500);
        }
    }
}
