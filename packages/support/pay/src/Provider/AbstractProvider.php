<?php

declare(strict_types=1);

namespace Support\Pay\Provider;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Psr\Http\Message\MessageInterface;
use Support\Pay\Contract\ProviderInterface;
use Support\Pay\Contract\ShortcutInterface;
use Support\Pay\Logger;
use Support\Pay\Rocket;

abstract class AbstractProvider implements ProviderInterface
{
    protected $config;

    public function call(string $shortcut, array $params = [])
    {
        if (!class_exists($shortcut) || !in_array(ShortcutInterface::class, class_implements($shortcut))) {
            throw new \Exception("[{$shortcut}] is not implement ShortcutInterface");
        }

        return $this->pay(app($shortcut)->getPlugins($params), $params);
    }

    public function pay(array|string $plugins, array $params): null|Collection|MessageInterface|Rocket
    {
        $plugins = is_string($plugins) ? [$plugins] : $plugins;

        Logger::info('[AbstractProvider] 即將進行 pay 操作', func_get_args());

        Event::dispatch('pay.started', ['plugins' => $plugins, 'params' => $params]); // Event::dispatch(new Event\PayStarted($plugins, $params, null));

        /** @var \Illuminate\Pipeline\Pipeline */
        $pipeline = app(\Illuminate\Pipeline\Pipeline::class);

        /** @var Rocket */
        $rocket = $pipeline
            ->send((new Rocket($this->config))->setParams($params)->setPayload(new Collection()))
            ->through($plugins)
            ->via('assembly')
            ->then(static fn ($rocket): Rocket => self::ignite($rocket))
            //
        ;

        Event::dispatch('pay.finish', ['rocket' => $rocket]); // Event::dispatch(new Event\PayFinish($rocket));

        return $rocket->getDestination();
    }

    public function query(array $order): Collection|Rocket
    {
        throw new \Exception('Provider does not support query api');
    }

    public function cancel(array $order): Collection|Rocket
    {
        throw new \Exception('Provider does not support cancel api');
    }

    public function close(array $order): Collection|Rocket
    {
        throw new \Exception('Provider does not support close api');
    }

    public function refund(array $order): Collection|Rocket
    {
        throw new \Exception('Provider does not support refund api');
    }

    public static function ignite(Rocket $rocket): Rocket
    {
        return $rocket;
    }
}
