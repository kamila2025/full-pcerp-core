<?php

namespace App\Enums\Transaction;

enum TransactionStatusEnum: string
{
    case 建立中 = 'created';
    case 處理中 = 'pending';
    case 已付款 = 'success';
    case 失敗 = 'failed';
}
