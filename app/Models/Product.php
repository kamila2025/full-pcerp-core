<?php

namespace App\Models;

use App\Enums\Product\ProductInventoryManagementEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Tags\HasTags;
use Spatie\Tags\Tag;

class Product extends Model
{
    use LogsActivity;

    use HasTags;

    protected $guarded = [];

    protected $casts = [
        'inventory_management'  => ProductInventoryManagementEnum::class,
    ];

    public function brands()
    {
        return $this->morphToMany(Tag::class, 'taggable')->where('type', 'brands');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_has_product');
    }

    public function variants()
    {
        return $this->hasMany(Variant::class)->orderBy('position');
    }

    public function types()
    {
        return $this->hasMany(VariantType::class)->orderBy('position');
    }

    public function inventories()
    {
        return $this->hasManyThrough(Inventory::class, Variant::class);
    }

    public function orderItems()
    {
        return $this->hasManyThrough(OrderItem::class, Variant::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(['created_at', 'updated_at']);
    }

    protected static function booted()
    {
        static::creating(function (Model $model) {
            if (empty($model->handle)) {
                $originalHandle = $handle = str_replace(' ', '-', Str::lower($model->name));

                $counter = 1;

                // 檢查 handle 是否存在，存在就遞增數字
                while (static::where('handle', $handle)->exists()) {
                    $handle = $originalHandle . '-' . $counter;

                    $counter++;
                }

                // 最終確定的 handle 賦值給模型
                $model->handle = $handle;
            }

            if (empty($model->position)) {
                $maxPosition = static::max('position');

                $model->position = is_null($maxPosition) ? 1 : $maxPosition + 1;
            }
        });

        static::deleting(function (Model $model) {
            if ($model->orderItems()->exists()) throw new \Exception($model->name . ' 有訂單項目，無法刪除');

            $model->inventories()->chunk(100, function ($inventories) {
                foreach ($inventories as $inventory) {
                    $inventory->activities()->delete();

                    $inventory->logs()->delete();

                    $inventory->delete();
                }
            });

            $model->variants()->delete();
        });
    }
}
