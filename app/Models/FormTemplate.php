<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormTemplate extends Model
{
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(FormTemplateItem::class)->orderBy('position');
    }
}
