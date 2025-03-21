<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Controllers;

use App\Http\Controllers\Controller;
use Callcocam\Plannerate\Http\Requests\Category\StoreCategoryRequest;
use Callcocam\Plannerate\Http\Requests\Category\UpdateCategoryRequest;
use Callcocam\Plannerate\Http\Resources\CategoryResource;
use Callcocam\Plannerate\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query()
            ->with('tenant')
            ->withCount('products')
            ->when($request->tenant_id, fn ($query) => $query->where('tenant_id', $request->tenant_id))
            ->when($request->status, fn ($query) => $query->where('status', $request->status))
            ->when($request->search, fn ($query) => $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('slug', 'like', "%{$request->search}%");
            }));

        $categories = $query->latest()->paginate();

        return Inertia::render('Categories/Index', [
            'categories' => $categories
                ->withQueryString()
                ->through(fn ($category) => [
                    'id' => $category->id,
                    'tenant_id' => $category->tenant_id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'status' => [
                        'value' => $category->status,
                        'label' => $category->status->label(),
                        'color' => $category->status->color(),
                    ],
                    'products_count' => $category->products_count,
                    'tenant' => [
                        'id' => $category->tenant->id,
                        'name' => $category->tenant->name,
                    ],
                    'created_at' => $category->created_at->format('d/m/Y H:i'),
                ]),
            'stats' => [
                'total' => Category::count(),
                'published' => Category::where('status', 'published')->count(),
                'draft' => Category::where('status', 'draft')->count(),
            ],
            'filters' => $request->only(['search', 'status', 'tenant_id']),
            'can' => [
                'create' => true, // auth()->user()->isAdmin(),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Categories/Create');
    }

    public function store(StoreCategoryRequest $request)
    {
        Category::create($request->validated());

        return redirect()->route('categories.index')
            ->with('message', 'Categoria criada com sucesso.');
    }

    public function edit(Category $category)
    {
        return Inertia::render('Categories/Edit', [
            'category' => [
                'id' => $category->id,
                'tenant_id' => $category->tenant_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'status' => $category->status,
                'tenant' => [
                    'id' => $category->tenant->id,
                    'name' => $category->tenant->name,
                ],
            ],
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')
            ->with('message', 'Categoria atualizada com sucesso.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('categories.index')
            ->with('message', 'Categoria excluída com sucesso.');
    }

    public function search()
    {
        $categories = Category::where('tenant_id', auth()->user()->tenant_id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }
}
