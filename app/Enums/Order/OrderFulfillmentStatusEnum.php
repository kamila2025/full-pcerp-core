<?php

namespace App\Enums\Order;

enum OrderFulfillmentStatusEnum: string
{
    case 未出貨 = 'unfulfilled';
    case 部分出貨 = 'partially_fulfilled';
    case 已出貨 = 'fulfilled';
    case 部分送達 = 'partially_delivered';
    case 已送達 = 'delivered';
    case 重新入庫 = 'restocked';
    case 自取 = 'pickup';
}
