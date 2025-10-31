<?php

namespace App\Enums\Order;

enum OrderFinancialStatusEnum: string
{
    case 未付款 = 'unpaid';
    case 貨到付款 = 'cod';
    case 部分付款 = 'partially_paid';
    case 已付款 = 'paid';
    case 已退款 = 'refunded';
}
