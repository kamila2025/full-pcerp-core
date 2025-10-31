<?php

namespace App\Notifications;

use App\Enums\Gateway\GatewayTypeEnum;
use App\Enums\Transaction\TransactionStatusEnum;
use App\Models\Transaction;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(public Transaction $transaction)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [Channels\TelegramChannel::class];
    }

    public function toTelegram(object $notifiable)
    {
        // 根據交易狀態和付款類型決定通知內容
        if ($this->transaction->gateway_type === GatewayTypeEnum::萬事達) {
            // 三方金流
            if ($this->transaction->status === TransactionStatusEnum::處理中) {
                // 創建付款連結階段
                $emoji = '🔗';
                $status = '三方金流付款連結已建立';
                $message = $emoji . " *付款連結建立通知*\n\n";
                $message .= "📋 訂單編號：`" . $this->transaction->order->order_number . "`\n";
                $message .= "💵 付款金額：`" . number_format($this->transaction->amount) . "`\n";
                $message .= "💳 付款方式：`" . ($this->transaction->gateway_title ?? '三方金流') . "`\n";
                $message .= "📊 狀態：`" . $status . "`\n\n";
                $message .= "⏳ 等待客戶完成付款...";
            } elseif ($this->transaction->status === TransactionStatusEnum::已付款) {
                // 付款成功回調階段
                $emoji = '✅';
                $status = '三方金流付款成功';
                $message = $emoji . " *付款成功通知*\n\n";
                $message .= "📋 訂單編號：`" . $this->transaction->order->order_number . "`\n";
                $message .= "💵 付款金額：`" . number_format($this->transaction->amount) . "`\n";
                $message .= "💳 付款方式：`" . ($this->transaction->gateway_title ?? '三方金流') . "`\n";
                $message .= "📊 狀態：`" . $status . "`\n\n";
                $message .= "✅ 付款已自動確認！";
            }
        } else {
            if ($this->transaction->status === TransactionStatusEnum::處理中) {
                // 手動付款
                $emoji = '💰';
                $status = '手動付款待對帳';
                $message = $emoji . " *付款通知*\n\n";
                $message .= "📋 訂單編號：`" . $this->transaction->order->order_number . "`\n";
                $message .= "💵 付款金額：`" . number_format($this->transaction->amount) . "`\n";
                $message .= "💳 付款方式：`" . ($this->transaction->gateway_title ?? '手動付款') . "`\n";
                $message .= "📊 狀態：`" . $status . "`\n\n";
                $message .= "⚠️ 請確認付款並更新訂單狀態！";
            }
        }

        return $message;
    }
}
