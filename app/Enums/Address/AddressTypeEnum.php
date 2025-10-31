<?php

namespace App\Enums\Address;

enum AddressTypeEnum: string
{
    case 客戶 = 'customer';
    case 訂單配送 = 'order_shipping';
    case 訂單自取 = 'order_pickup';
}
