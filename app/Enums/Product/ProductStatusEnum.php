<?php

namespace App\Enums\Product;

enum ProductStatusEnum: string
{
    case 上架 = 'published';
    case 下架 = 'unpublished';
}
