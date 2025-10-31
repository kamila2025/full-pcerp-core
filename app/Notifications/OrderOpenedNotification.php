<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class OrderOpenedNotification extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(public Order $order)
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
        $customerName = $this->order->customer?->name ?? '未指定客戶';
        $locationName = $this->order->location?->name ?? '未指定據點';
        $userName = $this->order->attributionUser?->name ?? '系統';

        return "📋 訂單已開啟！\n" .
            "訂單編號：{$this->order->order_number}\n" .
            "客戶：{$customerName}\n" .
            "據點：{$locationName}\n" .
            "處理人員：{$userName}\n" .
            "金額：NT$ " . number_format($this->order->amount) . "\n" .
            "開啟時間：" . $this->order->updated_at->setTimezone('Asia/Taipei')->format('Y-m-d H:i:s O');
    }
}
