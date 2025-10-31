<?php

namespace App\Listeners;

use App\Enums\Order\OrderFinancialStatusEnum;
use App\Enums\Transaction\TransactionStatusEnum;
use App\Events\OrderSavedFinancialStatus;
use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CheckAndSyncOrderFinancialStatus
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderSavedFinancialStatus $event): void
    {
        if (!$order = $event->order) return;

        // 確保訂單的財務狀態與交易狀態一致
        $totalTransactionAmount = Transaction::query()
            ->where('order_id', $order->getKey())
            ->where('status', TransactionStatusEnum::已付款)
            ->sum('amount');

        if ($totalTransactionAmount > 0) {
            $order->financial_status = $totalTransactionAmount >= $order->amount ? OrderFinancialStatusEnum::已付款 : OrderFinancialStatusEnum::部分付款;
        } else {
            $order->financial_status = OrderFinancialStatusEnum::未付款;
        }

        $order->save();
    }
}
