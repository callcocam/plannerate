<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Controllers;
 
use App\Http\Controllers\Controller;
use Callcocam\Plannerate\Enums\StoreStatus;
use Callcocam\Plannerate\Http\Requests\StoreStoreRequest;
use Callcocam\Plannerate\Http\Requests\UpdateStoreRequest;
use Callcocam\Plannerate\Models\Store;
use Callcocam\Raptor\Enums\AddressStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $query = Store::query()
            ->with('defaultAddress')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            });

        return Inertia::render('Stores/Index', [
            'stores' => $query->latest()
                ->paginate()
                ->withQueryString()
                ->through(fn ($store) => [
                    'id' => $store->id,
                    'name' => $store->name,
                    'code' => $store->code,
                    'email' => $store->email,
                    'phone' => $store->phone,
                    'address' => $store->defaultAddress ? [
                        'street' => $store->defaultAddress->street,
                        'number' => $store->defaultAddress->number,
                        'city' => $store->defaultAddress->city,
                        'state' => $store->defaultAddress->state,
                    ] : null,
                    'status' => [
                        'value' => $store->status->value,
                        'label' => $store->status->label(),
                    ],
                ]),
            'filters' => $request->only(['search', 'status']),
            'stats' => [
                'total' => Store::count(),
                'published' => Store::where('status', StoreStatus::PUBLISHED)->count(),
                'draft' => Store::where('status', StoreStatus::DRAFT)->count(),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Stores/Create');
    }

    public function store(StoreStoreRequest $request)
    {
        $data = $request->validated();
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['slug'] = Str::slug($data['name']);

        $store = Store::create($data);

        // Criar endereço padrão
        if ($request->has('address')) {
            $store->addAddress([
                'name' => 'Principal',
                'recipient' => $store->name,
                'phone' => $store->phone,
                'street' => $request->input('address.street'),
                'number' => $request->input('address.number'),
                'complement' => $request->input('address.complement'),
                'district' => $request->input('address.district'),
                'city' => $request->input('address.city'),
                'state' => $request->input('address.state'),
                'zip_code' => $request->input('address.zip_code'),
                'is_default' => true,
                'status' => AddressStatus::PUBLISHED,
            ]);
        }

        return redirect()->route('stores.index')
            ->with('message', 'Loja criada com sucesso.');
    }

    public function edit(Store $store)
    {
        return Inertia::render('Stores/Edit', [
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'code' => $store->code,
                'phone' => $store->phone,
                'email' => $store->email,
                'status' => $store->status->value,
                'addresses' => $store->addresses()->get()->map(fn ($address) => [
                    'id' => $address->id,
                    'street' => $address->street,
                    'number' => $address->number,
                    'complement' => $address->complement,
                    'district' => $address->district,
                    'city' => $address->city,
                    'state' => $address->state,
                    'zip_code' => $address->zip_code,
                    'is_default' => $address->is_default,
                    'status' => $address->status->value,
                ]),
            ],
        ]);
    }

    public function update(UpdateStoreRequest $request, Store $store)
    {
        $data = $request->validated();

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $store->update($data);

        // Atualizar endereço padrão
        if ($request->has('address')) {
            if ($store->defaultAddress) {
                $store->updateAddress($store->defaultAddress, [
                    'street' => $request->input('address.street'),
                    'number' => $request->input('address.number'),
                    'complement' => $request->input('address.complement'),
                    'district' => $request->input('address.district'),
                    'city' => $request->input('address.city'),
                    'state' => $request->input('address.state'),
                    'zip_code' => $request->input('address.zip_code'),
                ]);
            } else {
                $store->addAddress([
                    'name' => 'Principal',
                    'recipient' => $store->name,
                    'phone' => $store->phone,
                    'street' => $request->input('address.street'),
                    'number' => $request->input('address.number'),
                    'complement' => $request->input('address.complement'),
                    'district' => $request->input('address.district'),
                    'city' => $request->input('address.city'),
                    'state' => $request->input('address.state'),
                    'zip_code' => $request->input('address.zip_code'),
                    'is_default' => true,
                    'status' => AddressStatus::PUBLISHED,
                ]);
            }
        }

        return redirect()->route('stores.index')
            ->with('message', 'Loja atualizada com sucesso.');
    }

    public function destroy(Store $store)
    {
        $store->addresses()->delete(); // Excluir endereços relacionados
        $store->delete();

        return redirect()->route('stores.index')
            ->with('message', 'Loja excluída com sucesso.');
    }
}
