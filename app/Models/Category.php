<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use SolutionForest\FilamentTree\Concern\ModelTree;

class Category extends Model
{
    use ModelTree;

    protected $guarded = [];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'category_has_product');
    }

    public function determineTitleColumnName(): string
    {
        return 'name';
    }

    public function determineOrderColumnName(): string
    {
        return 'position';
    }

    public static function defaultParentKey()
    {
        return null;
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            // --------- Handle 處理 ---------
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

            // --------- Position 處理 ---------
            if (empty($model->position)) {
                $maxPosition = static::max('position');

                $model->position = is_null($maxPosition) ? 1 : $maxPosition + 1;
            }
        });
    }
}
