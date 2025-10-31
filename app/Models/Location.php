<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Location extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'location_has_user')->withTrashed();
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function channels()
    {
        return $this->morphToMany(Channel::class, 'channelable')->withTimestamps();
    }

    public function scopeWithUsers($query)
    {
        return $query->whereRelation('users', 'users.id', auth()->id())->orderBy('is_primary', 'desc');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            // --------- Code 處理 ---------
            if (empty($model->code)) {
                $originalCode = $code = str_replace(' ', '-', Str::lower($model->name));

                $counter = 1;

                // 檢查 code 是否存在，存在就遞增數字
                while (static::where('code', $code)->exists()) {
                    $code = $originalCode . '-' . $counter;

                    $counter++;
                }

                $model->code = $code;
            }

            // --------- Is Primary 處理 ---------
            if (empty($model->is_primary)) {
                $model->is_primary = !static::where('is_primary', true)->exists();
            }
        });

        static::saving(function ($model) {
            // 當有人設 is_primary 為 true 時，其他資料都改為 false
            if ($model->is_primary) {
                static::where('id', '!=', $model->id)->where('is_primary', true)->update(['is_primary' => false]);
            }
        });

        static::deleting(function ($model) {
            if ($model->is_primary) throw new \Exception('無法刪除主要據點');

            if ($model->inventories()->exists()) throw new \Exception('無法刪除有庫存的據點');
        });
    }
}
