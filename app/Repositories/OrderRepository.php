<?php

namespace App\Repositories;

use App\Enums\PurchaseOrder\PurchaseOrderStatusEnum;
use App\Models\Order;

class OrderRepository extends Repository
{
    protected $fieldSearchable = [
        'order_number' => 'like',
        'customer.name' => 'like',
        'items.product.name' => 'like',
        'items.product_name' => 'like',
        'attribution_user_id' => 'in',
        'location_id' => 'in',
        'customer_id' => 'in',
        'fulfillment_status' => 'in',
        'financial_status' => 'in',
        'status' => 'in',
        'created_at' => 'timestamp',
    ];

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model(): string
    {
        return Order::class;
    }

    public function getOrders($attributes)
    {
        $this->applyCriteria();

        $query = $this->model->with('attributionUser:id,name', 'customer:id,name', 'template:id,name,icon');

        return $query->latest()->paginate($attributes['per_page'] ?? null);
    }

    public function getOrderWithRelations($id)
    {
        return $this->model->with([
            'attributionUser:id,name',
            'location',
            'shippingLocation',
            'shippingFees',
            'items.product.categories',
            'items.fulfillmentItems',
            'items.purchaseOrderItems.purchaseOrder',
            'items.inventories:location_id,variant_id,quantity',
            'customer',
            'shippingAddress',
            'pickupAddress.location',
            'transactions',
            'fulfillments.items',
            'fulfilledItems.fulfillment',
        ])->findOrFail($id);
    }
}
