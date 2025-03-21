<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Callcocam\Plannerate\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {

        // hangable: true
        // stackable: false
        // flammable: true
        // perishable: false
        $query = Product::query()
            ->with(['tenant', 'category', 'image'])
            ->when($request->tenant_id, fn ($query) => $query->where('tenant_id', $request->tenant_id))
            ->when($request->category, fn ($query) => $query->where('category_id', $request->category))
            ->when($request->status, fn ($query) => $query->where('status', $request->status))
            ->when($request->hangable, fn ($query) => $query->where('hangable', $request->hangable))
            ->when($request->stackable, fn ($query) => $query->where('stackable', $request->stackable))
            ->when($request->flammable, fn ($query) => $query->where('flammable', $request->flammable))
            ->when($request->perishable, fn ($query) => $query->where('perishable', $request->perishable))
            ->when($request->search, fn ($query) => $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('ean', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%");
            }));

        $products = $query->latest()->limit(10)->get();

        return $products;
    }
}
