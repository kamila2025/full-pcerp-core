<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormTemplateItem extends Model
{
    protected $guarded = [];

    public function formTemplate()
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
