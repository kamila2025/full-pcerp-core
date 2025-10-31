<?php

namespace App\Enums\Fulfillment;

enum FulfillmentCompanyEnum: string
{
    case 中華郵政 = 'chunghwa';
    case 大榮貨運 = 'kerry';
    case 新竹物流 = 'hct-logistic';
    case 順豐快遞 = 'sf-express';
    case 黑貓宅急便 = 't-cat';
}
