<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository extends Repository
{
    protected $fieldSearchable = [
        'name' => 'like',
        'categories.category_id' => 'in',
        'brands.tag_id' => 'in',
    ];

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model(): string
    {
        return Product::class;
    }

    public function getProducts($attributes)
    {
        $this->applyCriteria();

        return $this->model
            ->withMin('variants', 'price')
            ->withMax('variants', 'price')
            ->withSum('inventories', 'quantity')
            ->withCount('variants')
            ->orderBy('position', 'desc')
            ->paginate($attributes['per_page'] ?? null);
    }
}
