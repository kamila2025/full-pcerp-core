<?php

namespace App\Enums\Order;

enum OrderDeliveryTypeEnum: string
{
    case 運送 = 'shipping';
    case 自取 = 'pickup';
    case 其他 = 'others';
}
