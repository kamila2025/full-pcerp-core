<?php

namespace App\Enums\TransferOrder;

enum TransferOrderArrivalStatusEnum: string
{
    case 未到貨 = 'pending';
    case 部分到貨 = 'partial_received';
    case 已到貨 = 'received';
}