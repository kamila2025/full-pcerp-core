<?php

namespace App\Enums\PurchaseOrder;

enum PurchaseOrderTypeEnum: string
{
    case 進貨 = 'purchase';
    case 退貨 = 'return';
}