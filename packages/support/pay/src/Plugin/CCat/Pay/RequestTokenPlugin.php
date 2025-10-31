<?php

declare(strict_types=1);

namespace Support\Pay\Plugin\CCat\Pay;

use Illuminate\Support\Collection;
use Support\Pay\Contract\PluginInterface;
use Support\Pay\Logger;
use Support\Pay\PayManager;
use Support\Pay\Rocket;

class RequestTokenPlugin implements PluginInterface
{
    public const URL = [
        PayManager::MODE_NORMAL    => 'https://cocs.4128888card.com.tw/Token',
        PayManager::MODE_SANDBOX   => 'http://test.4128888card.com.tw/app/Token',
        PayManager::MODE_SERVICE   => 'http://test.4128888card.com.tw/app/Token',
    ];

    public function assembly(Rocket $rocket, \Closure $next): Rocket
    {
        Logger::debug('[CCat][RequestTokenPlugin] 插件開始裝載', ['rocket' => $rocket]);

        // 傳遞參數
        $rocket->setPayload([
            'grant_type'    => 'password',
            'username'      => $rocket->getConfig()->get('username'),
            'password'      => $rocket->getConfig()->get('password'),
        ]);

        // 送出請求
        $response = (new \GuzzleHttp\Client())->sendRequest((new \GuzzleHttp\Psr7\Request(
            'POST',
            self::URL[$rocket->getConfig()->get('mode', PayManager::MODE_SANDBOX)],
            [
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            http_build_query($rocket->getPayload()->toArray())
        )));

        //dd($response->getBody()->getContents());

        // 設定回應
        $rocket
            ->setDestinationOrigin($response)
            ->setDestination(new Collection($data = json_decode($response->getBody()->getContents(), true)))
            ->setPayload(new Collection($data));

        Logger::info('[CCat][RequestTokenPlugin] 插件裝載完畢', ['rocket' => $rocket]);

        return $next($rocket);
    }

    protected function getHeaders(Rocket $rocket): array
    {
        return [
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ];
    }
}
