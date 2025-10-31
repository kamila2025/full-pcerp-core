<?php

namespace App\Http\Controllers;

use App\Enums\InventoryLog\InventoryLogTypeEnum;
use App\Enums\PermissionNameEnum;
use App\Enums\Product\ProductInventoryManagementEnum;
use App\Events\InventoryChanged;
use App\Exports\InventoryExport;
use App\Imports\InventoryImport;
use App\Models\InventoryLog;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class AdminInventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize(PermissionNameEnum::庫存管理);

        $attributes = $request->validate([
            'page'      => 'nullable|integer',
            'per_page'  => 'nullable|max:100|integer|multiple_of:5',
        ]);

        $locationIds = $request->has('location_id') ? explode(',', $request->input('location_id')) : null;

        return Inertia::render('Inventory/Index', [
            'variants' => Variant::query()
                ->with([
                    'product',
                    'inventories' => fn ($query) => $query->when($locationIds, fn ($query) => $query->whereIn('location_id', $locationIds)),
                ])
                ->has('inventories')
                ->whereRelation('product', 'inventory_management', ProductInventoryManagementEnum::庫存管理)
                ->when($request->has('inventory_quantity_min'), fn ($query) => $query->whereRelation('inventories', fn ($query) => $query->where('quantity', '>', $request->input('inventory_quantity_min'))->when($locationIds, fn ($query) => $query->whereIn('location_id', $locationIds))))
                ->when($request->has('inventory_quantity_max'), fn ($query) => $query->whereRelation('inventories', fn ($query) => $query->where('quantity', '<', $request->input('inventory_quantity_max'))->when($locationIds, fn ($query) => $query->whereIn('location_id', $locationIds))))
                ->latest()
                ->paginate($attributes['per_page'] ?? null),
            'locations' => Location::query()->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::庫存管理);

        $attributes = $request->validate([
            'inventories' => 'required|array',
            'inventories.*.location_id' => 'required|integer|exists:locations,id',
            'inventories.*.quantity' => 'required|integer',
        ]);

        DB::transaction(function () use ($id, $attributes) {
            $variant = Variant::findOrFail($id);

            foreach ($attributes['inventories'] as $inventory) {
                event(new InventoryChanged(
                    type: InventoryLogTypeEnum::庫存更新,
                    variantId: $variant->getKey(),
                    locationId: $inventory['location_id'],
                    quantity: $inventory['quantity'],
                    causer: auth()->user(),
                ));
            }
        });

        return redirect()->route('inventories.index');
    }

    public function logs(Request $request)
    {
        Gate::authorize(PermissionNameEnum::庫存管理);

        $attributes = $request->validate([
            'product_id'  => 'nullable|integer',
            'location_id' => 'nullable|integer',
        ]);

        if (!isset($attributes['product_id'])) abort(404);

        return Inertia::render('Inventory/Log', [
            'locations' => Location::query()->get(),
            'product' => $product = Product::findOrFail($attributes['product_id']),
            'logs' => InventoryLog::query()
                ->with('product:id,name', 'variant:id,name', 'location:id,name', 'causer:id,name,email')
                ->with(['reference' => fn ($morphTo) => $morphTo->morphWith([
                    Order::class => ['order:id,order_number'],
                    PurchaseOrder::class => ['purchaseOrder:id,order_number'],
                ])])
                ->where('product_id', $product->getKey())
                ->when(isset($attributes['location_id']), fn ($query) => $query->where('location_id', $attributes['location_id']))
                ->latest('created_at')
                ->latest('id')
                ->paginate($attributes['per_page'] ?? null),
        ]);
    }

    public function export(Request $request)
    {
        Gate::authorize(PermissionNameEnum::庫存管理);

        $time = now()->format('Ymd_His');

        return Excel::download(new InventoryExport, "庫存_{$time}.xlsx");
    }

    public function import(Request $request)
    {
        Gate::authorize(PermissionNameEnum::庫存管理);

        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        Excel::import(new InventoryImport, $request->file('file'));

        return back()->with('success', '庫存資料已匯入');
    }
}
