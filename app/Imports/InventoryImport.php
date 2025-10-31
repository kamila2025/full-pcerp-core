<?php

namespace App\Imports;

use App\Enums\InventoryLog\InventoryLogTypeEnum;
use App\Events\InventoryChanged;
use App\Models\Inventory;
use App\Models\Location;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

HeadingRowFormatter::default(HeadingRowFormatter::FORMATTER_NONE);

class InventoryImport implements ToModel, WithHeadingRow
{
    protected $locations;

    public function __construct()
    {
        $this->locations = Location::all()->pluck('id', 'name')->toArray();
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        for ($i = 2; $i < count($row); $i++) {
            if (!$location = Location::where('name', $key = array_keys($row)[$i])->first()) return null;

            $inventory = Inventory::query()
                ->whereRelation('product', 'handle', $row[array_keys($row)[0]])
                ->where('location_id', $location->getKey())
                ->first();

            if ($inventory && !is_null($row[$key])) {
                event(new InventoryChanged(
                    type: InventoryLogTypeEnum::批量庫存更新,
                    variantId: $inventory->variant_id,
                    locationId: $inventory->location_id,
                    quantity: (int) $row[$key],
                    causer: auth()->user(),
                ));
            }
        }

        return null;
    }
}
