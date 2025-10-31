<?php

namespace App\Enums\Fulfillment;

enum FulfillmentServiceEnum: string
{
    case 手動出貨 = 'manual';
    case 黑貓宅急便 = 't-cat';
}
