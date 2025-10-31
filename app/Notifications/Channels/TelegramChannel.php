<?php

namespace App\Notifications\Channels;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\RequestException;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramChannel
{
    public function __construct(protected Dispatcher $dispatcher)
    {
        //
    }

    public function send($notifiable, Notification $notification)
    {
        try {
            // @phpstan-ignore-next-line
            $message = $notification->toTelegram($notifiable);

            if (!$routing = $notifiable->routeNotificationFor('telegram')) throw new \Exception('Telegram 通知設定不完整，請檢查 chat_id 和 bot_token 設定');

            if (is_string($message)) {
                $payload = [
                    'chat_id' => data_get($routing, 'chat_id'),
                    'text' => $message,
                ];
            }

            // https://core.telegram.org/bots/api#sendmessage
            $response = Http::baseUrl('https://api.telegram.org/bot' . data_get($routing, 'bot_token'))
                ->post('/sendMessage', $payload);

            if ($response->failed()) throw new RequestException($response);

            return json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            $this->dispatcher->dispatch(new NotificationFailed($notifiable, $notification, self::class, ['error' => $e->getMessage()]));

            Log::error($e->getMessage());

            return null;
        }
    }
}
