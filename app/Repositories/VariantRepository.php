<?php

namespace App\Repositories;

use App\Models\Variant;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class VariantRepository extends Repository
{

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model(): string
    {
        return Variant::class;
    }

    public function getVariantsWithRelations($attributes = [])
    {
        return $this->model
            ->with([
                'product.categories',
                'product.brands' => fn ($query) => match (config('database.default')) {
                    'mysql' => $query->select('*', DB::raw("JSON_UNQUOTE(JSON_EXTRACT(name, '$." . app()->getLocale() . "')) as name_display")),
                    'sqlite' => $query->select('*', DB::raw("json_extract(name, '$." . app()->getLocale() . "') as name_display")),
                },
                'inventories:location_id,variant_id,quantity',
            ])
            ->withSum('inventories', 'quantity')
            ->withSum('orderItems', 'quantity')
            ->when(isset($attributes['category_id']), fn ($query) => $query->whereRelation('product.categories', 'categories.id', $attributes['category_id']))
            ->orderByDesc(Product::select('position')->whereColumn('products.id', 'variants.product_id'))
            ->orderBy('position')
            ->get();
    }
}
