<?php

namespace App\Enums\Order;

enum OrderSourceTypeEnum: string
{
    case 後台       = 'admin_panel';
    case 商店       = 'storefront';
    case ERP        = 'erp';
    case POS        = 'pos';
    case 批發       = 'wholesale';
    case LINE       = 'line';
}
