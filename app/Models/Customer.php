<?php

namespace App\Models;

use App\Enums\Customer\CustomerStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Customer extends Model
{
    use LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'additional' => 'array',
        'status' => CustomerStatusEnum::class,
    ];

    public function attributionLocation()
    {
        return $this->belongsTo(Location::class, 'attribution_location_id');
    }

    public function attributionUser()
    {
        return $this->belongsTo(User::class, 'attribution_user_id')->withTrashed();
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'customer_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(['created_at', 'updated_at']);
    }

    public function scopeOwnedByUser($query)
    {
        return $query->where('attribution_user_id', auth()->id());
    }

    protected static function booted()
    {
        static::creating(function (Model $model) {
            if (empty($model->status)) $model->status = CustomerStatusEnum::未驗證;
        });

        static::deleting(function (Model $model) {
            if ($model->orders()->exists()) {
                throw new \Exception('此客戶有關聯訂單，無法刪除');
            }
        });
    }
}
