<?php

namespace App\Models;

use App\Enums\Address\AddressTypeEnum;
use App\Enums\Order\OrderDeliveryTypeEnum;
use App\Enums\Order\OrderFinancialStatusEnum;
use App\Enums\Order\OrderFulfillmentStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\PermissionNameEnum;
use App\Events\OrderChanged;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class Order extends Model
{
    protected $guarded = [];

    protected $casts = [
        'tax_rate' => 'integer',
        'subtotal_price' => 'integer',
        'total_discount' => 'integer',
        'custom_discount' => 'integer',
        'shipping_discount' => 'integer',
        'total_shipping' => 'integer',
        'total_tax' => 'integer',
        'total_price' => 'integer',
        'custom_amount' => 'integer',
        'amount' => 'integer',
        'delivery_type' => OrderDeliveryTypeEnum::class,
        'fulfillment_status' => OrderFulfillmentStatusEnum::class,
        'financial_status' => OrderFinancialStatusEnum::class,
        'status' => OrderStatusEnum::class,
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function shippingLocation()
    {
        return $this->belongsTo(Location::class);
    }

    public function template()
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function attributionUser()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function shippingAddress()
    {
        return $this->hasOne(Address::class)->where('address_type', AddressTypeEnum::訂單配送);
    }

    public function pickupAddress()
    {
        return $this->hasOne(Address::class)->where('address_type', AddressTypeEnum::訂單自取);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function fulfillments()
    {
        return $this->hasMany(Fulfillment::class);
    }

    public function fulfilledItems()
    {
        return $this->hasManyThrough(FulfillmentItem::class, Fulfillment::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shippingFees()
    {
        return $this->hasMany(ShippingFee::class);
    }

    /**
     * 篩選 timestamp 區間查詢（秒）
     */
    public function scopeWhereTimestampBetween(Builder $query, string $column, int $start, int $end): Builder
    {
        return match (DB::getDriverName()) {
            'sqlite' => $query->whereRaw("strftime('%s', substr($column, 1, 19)) BETWEEN ? AND ?", [(string) $start, (string) $end]),
            'mysql'  => $query->whereRaw("UNIX_TIMESTAMP($column) BETWEEN ? AND ?", [$start, $end]),
        };
    }

    protected static function booted()
    {
        static::addGlobalScope('attribution_user_id', function (Builder $builder) {
            if (!auth()->check() || Gate::check(PermissionNameEnum::所有權限)) return;

            $locationIds = Location::whereRelation('users', 'users.id', auth()->id())->pluck('id');

            $builder
                ->where('attribution_user_id', auth()->id())
                ->when(Gate::check(PermissionNameEnum::收款明細) || Gate::check(PermissionNameEnum::出貨明細), fn ($q) => $q->orWhereIn('location_id', $locationIds));
        });

        static::creating(function (Model $model) {
            $model->order_number = now()->format('YmdHis');

            if (empty($model->attribution_user_id)) $model->attribution_user_id = auth()->id();

            if (empty($model->location_id)) $model->location_id = Location::withUsers()->value('id');

            if (empty($model->delivery_type)) $model->delivery_type = OrderDeliveryTypeEnum::其他;

            if (empty($model->fulfillment_status)) $model->fulfillment_status = OrderFulfillmentStatusEnum::未出貨;

            if (empty($model->financial_status)) $model->financial_status = ($model->amount === 0 ? OrderFinancialStatusEnum::已付款 : OrderFinancialStatusEnum::未付款);

            if (empty($model->status)) $model->status = OrderStatusEnum::開啟;
        });

        static::created(function (Model $model) {
            event(new OrderChanged($model, changes: $model->getChanges(), isNew: true));
        });

        static::updating(function (Model $model) {
            if (Gate::check(PermissionNameEnum::所有權限)) return;

            if ($model->getOriginal('status') && !$model->getOriginal('status')->canEdit()) {
                throw new \Exception('訂單已經封存，無法修改');
            }
        });

        static::updated(function (Model $model) {
            event(new OrderChanged($model, changes: $model->getChanges(), isNew: false));
        });

        static::deleting(function (Model $model) {
            if (!$model->status->canDelete()) throw new \Exception('訂單已經封存，無法刪除');
        });
    }
}
