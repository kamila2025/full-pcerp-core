<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Channel extends Model
{
    use Notifiable;

    protected $guarded = [];

    protected $casts = [
        'settings' => 'array',
    ];

    public function routeNotificationForTelegram(): array
    {
        return [
            'chat_id' => $this->settings['telegram_chat_id'],
            'bot_token' => $this->settings['telegram_bot_token'],
        ];
    }

    public function locations()
    {
        return $this->morphedByMany(Location::class, 'channelable');
    }
}
