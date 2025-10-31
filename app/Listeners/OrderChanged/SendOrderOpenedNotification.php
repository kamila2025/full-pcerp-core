<?php

namespace App\Listeners\OrderChanged;

use App\Enums\Order\OrderStatusEnum;
use App\Events\OrderChanged;
use App\Models\Channel;
use App\Notifications\OrderOpenedNotification;
use Illuminate\Support\Facades\Notification;

class SendOrderOpenedNotification
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
    public function handle(OrderChanged $event): void
    {
        // 檢查訂單狀態是否變更為開啟
        if ($event->order->status === OrderStatusEnum::開啟 && ($event->isNew || isset($event->changes['status']))) {
            $notifiables = Channel::whereRelation('locations', 'locations.id', $event->order->location_id)->get();

            Notification::sendNow($notifiables, new OrderOpenedNotification($event->order));
        }
    }
}
