<?php

namespace App\Enums\Customer;

enum CustomerStatusEnum: string
{
    case 未驗證 = 'unverified';
    case 已驗證 = 'verified';
    case 黑名單 = 'blacklisted';
}