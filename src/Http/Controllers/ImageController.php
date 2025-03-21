<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Controllers;

use App\Http\Controllers\Controller;
use Callcocam\Plannerate\Http\Requests\StoreImageRequest;
use Callcocam\Plannerate\Http\Requests\UpdateImageRequest;
use Callcocam\Plannerate\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ImageController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Images/Index', [
            'images' => Image::query()
                ->with('user')
                ->when($request->status, fn ($query) => $query->where('status', $request->status))
                ->when($request->search, fn ($query) => $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                        ->orWhere('alt_text', 'like', "%{$request->search}%");
                }))
                ->latest()
                ->paginate()
                ->withQueryString()
                ->through(fn ($image) => [
                    'id' => $image->id,
                    'name' => $image->name,
                    'url' => Storage::url($image->path),
                    'thumbnail' => Storage::url($image->thumbnail_path),
                    'size' => $this->formatSize($image->size),
                    'dimensions' => "{$image->width}x{$image->height}px",
                    'status' => $image->status,
                    'user' => [
                        'name' => $image->user->name,
                    ],
                    'created_at' => $image->created_at->format('d/m/Y H:i'),
                ]),
            'filters' => $request->only(['search', 'status']),
            'stats' => [
                'total' => Image::count(),
                'total_size' => $this->formatSize(Image::sum('size')),
                'published' => Image::where('status', 'published')->count(),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Images/Create');
    }

    public function store(StoreImageRequest $request)
    {
        $file = $request->file('image');
        $extension = $file->getClientOriginalExtension();
        $slug = Str::slug($request->name);
        $path = $file->storeAs('images', "{$slug}.{$extension}", 'public');

        [$width, $height] = getimagesize($file->path());

        Image::create([
            'user_id' => auth()->id(),
            'path' => $path,
            'name' => $request->name,
            'slug' => $slug,
            'extension' => $extension,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'alt_text' => $request->alt_text,
            'status' => $request->status,
        ]);

        return redirect()->route('images.index')
            ->with('message', 'Imagem enviada com sucesso.');
    }

    public function edit(Image $image)
    {
        return Inertia::render('Images/Edit', [
            'image' => [
                'id' => $image->id,
                'name' => $image->name,
                'slug' => $image->slug,
                'alt_text' => $image->alt_text,
                'status' => $image->status,
                'url' => Storage::url($image->path),
                'dimensions' => "{$image->width}x{$image->height}px",
                'size' => $this->formatSize($image->size),
            ],
        ]);
    }

    public function update(UpdateImageRequest $request, Image $image)
    {
        $image->update($request->validated());

        return redirect()->route('images.index')
            ->with('message', 'Imagem atualizada com sucesso.');
    }

    public function destroy(Image $image)
    {
        Storage::disk('public')->delete($image->path);
        if ($image->thumbnail_path) {
            Storage::disk('public')->delete($image->thumbnail_path);
        }

        $image->delete();

        return redirect()->route('images.index')
            ->with('message', 'Imagem excluída com sucesso.');
    }

    private function formatSize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $size > 0 ? floor(log($size, 1024)) : 0;

        return number_format($size / pow(1024, $power), 2, ',', '.').' '.$units[$power];
    }
}
