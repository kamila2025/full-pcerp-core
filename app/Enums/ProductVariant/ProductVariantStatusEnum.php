<?php

namespace App\Enums\ProductVariant;

enum ProductVariantStatusEnum: string
{
    case 啟用 = 'enabled';
    case 停用 = 'disabled';
}
