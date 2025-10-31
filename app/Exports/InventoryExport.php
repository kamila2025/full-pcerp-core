<?php

namespace App\Exports;

use App\Enums\Product\ProductInventoryManagementEnum;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Variant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InventoryExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $locations = Location::all();

        $variants = Variant::query()
            ->with('product:id,name,handle', 'inventories')
            ->whereHas('product', fn ($query) => $query->where('inventory_management', ProductInventoryManagementEnum::庫存管理))
            ->get();

        $data = [];

        foreach ($variants as $variant) {
            $row = [
                'Handle' => $variant->product->handle,
                'Product Name' => $variant->product->name,
            ];

            foreach ($locations as $location) {
                $inventory = $variant->inventories->where('location_id', $location->id)->first();

                $row[$location->name] = $inventory ? strval($inventory->quantity) : 0;
            }

            $data[] = $row;
        }

        return collect($data);
    }

    public function headings(): array
    {
        $locations = Location::all()->pluck('name')->toArray();

        return array_merge(['Handle', 'Product Name'], $locations);
    }
}
