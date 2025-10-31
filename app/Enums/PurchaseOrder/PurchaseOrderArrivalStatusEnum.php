<?php

namespace App\Enums\PurchaseOrder;

enum PurchaseOrderArrivalStatusEnum: string
{
    case 未到貨 = 'pending_to_receive';
    case 部分到貨 = 'partial';
    case 已到貨 = 'all_received';
    case 未退貨 = 'pending_to_return';
    case 已退貨 = 'all_returned';
}