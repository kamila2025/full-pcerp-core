<?php

namespace App\Listeners\TransactionChanged;

use App\Events\TransactionChanged;
use App\Models\Channel;
use App\Notifications\PaymentReceivedNotification;
use Illuminate\Support\Facades\Notification;

class SendPaymentReceivedNotification
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
    public function handle(TransactionChanged $event): void
    {
        $notifiables = Channel::whereRelation('locations', 'locations.id', $event->transaction->order->location_id)->get();

        Notification::sendNow($notifiables, new PaymentReceivedNotification($event->transaction));
    }
}
