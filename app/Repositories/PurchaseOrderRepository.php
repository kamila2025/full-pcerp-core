<?php

namespace App\Repositories;

use App\Models\PurchaseOrder;

class PurchaseOrderRepository extends Repository
{
    protected $fieldSearchable = [
        'items.order.order_number' => 'like',
        'location_id' => 'in',
        'status' => 'in',
        'arrival_status' => 'in',
        'created_at' => 'timestamp',
    ];

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model(): string
    {
        return PurchaseOrder::class;
    }

    public function getOrders($attributes)
    {
        $this->applyCriteria();

        $query = $this->model->withSum('items', 'quantity');

        return $query->latest()->paginate($attributes['per_page'] ?? null);
    }
}
