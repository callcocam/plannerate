<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Controllers;

use App\Http\Controllers\Controller;
use Callcocam\Plannerate\Http\Requests\Product\StoreProductRequest;
use Callcocam\Plannerate\Http\Requests\Product\UpdateProductRequest;
use Callcocam\Plannerate\Http\Resources\ProductResource;
use Callcocam\Plannerate\Models\Category;
use Callcocam\Plannerate\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->with(['tenant', 'category', 'image'])
            ->when($request->tenant_id, fn ($query) => $query->where('tenant_id', $request->tenant_id))
            ->when($request->category_id, fn ($query) => $query->where('category_id', $request->category_id))
            ->when($request->status, fn ($query) => $query->where('status', $request->status))
            ->when($request->search, fn ($query) => $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('ean', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%");
            }));

        $products = $query->latest()->paginate();

        return Inertia::render('Products/Index', [
            'products' => $products
                ->withQueryString()
                ->through(fn ($product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'ean' => $product->ean,
                    'price' => number_format($product->price, 2, ',', '.'),
                    'image_url' => $product->image_url,
                    'status' => [
                        'value' => $product->status,
                        'label' => $product->status->label(),
                        'color' => $product->status->color(),
                    ],
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                    ] : null,
                    'image' => $product->image ? [
                        'url' => $product->image->url,
                    ] : null,
                    'created_at' => $product->created_at->format('d/m/Y H:i'),
                ]),
            'stats' => [
                'total' => Product::count(),
                'published' => Product::where('status', 'published')->count(),
                'draft' => Product::where('status', 'draft')->count(),
            ],
            'filters' => $request->only(['search', 'status', 'category_id', 'tenant_id']),
            'can' => [
                'create' => true, // auth()->user()->isAdmin(),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Products/Create', [
            'categories' => Category::select('id', 'name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        Product::create($data);

        return redirect()->route('products.index')
            ->with('message', 'Produto criado com sucesso.');
    }

    public function show(Product $product)
    {
        return new ProductResource($product);
    }

    public function edit(Product $product)
    {
        return Inertia::render('Products/Edit', [
            'product' => [
                'id' => $product->id,
                'tenant_id' => $product->tenant_id,
                'category_id' => $product->category_id,
                'image_id' => $product->image_id,
                'name' => $product->name,
                'slug' => $product->slug,
                'ean' => $product->ean,
                'width' => (string) $product->width,
                'height' => (string) $product->height,
                'depth' => (string) $product->depth,
                'facing' => (string) $product->facing,
                'weight' => (string) $product->weight,
                'price' => (string) $product->price,
                'stackable' => $product->stackable,
                'perishable' => $product->perishable,
                'flammable' => $product->flammable,
                'hangable' => $product->hangable,
                'description' => $product->description,
                'status' => $product->status,
            ],
            'categories' => Category::select('id', 'name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return redirect()->route('products.index')
            ->with('message', 'Produto atualizado com sucesso.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')
            ->with('message', 'Produto excluído com sucesso.');
    }

    public function search(Request $request)
    {
        $query = Product::query()
            ->with('category')
            ->where('tenant_id', auth()->user()->tenant_id);

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        if ($request->display_type === 'stackable') {
            $query->where('stackable', true);
        } elseif ($request->display_type === 'hangable') {
            $query->where('hangable', true);
        }

        $products = $query->paginate();

        return ProductResource::collection($products);
    }
}
