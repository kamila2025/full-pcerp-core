<?php

declare(strict_types=1);

namespace Support\Pay\Provider;

use Illuminate\Support\Collection;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Support\Pay\Contract\ProviderInterface;
use Support\Pay\Plugin\CCat\CallbackPlugin;
use Support\Pay\Rocket;
use Support\Pay\Shortcut\CCat\QueryShortcut;
use Support\Pay\Shortcut\CCat\WebShortcut;

class CCat extends AbstractProvider implements ProviderInterface
{
    public function __construct(protected $config = [])
    {
        if (!isset($config['username'])) throw new \Exception(class_basename(__CLASS__) . ' `username` is required');

        if (!isset($config['password'])) throw new \Exception(class_basename(__CLASS__) . ' `password` is required');
    }

    public function web(array $params): null|Collection|MessageInterface|Rocket
    {
        return $this->call(WebShortcut::class, $params);
    }

    public function callback(null|array|ServerRequestInterface $contents = null, ?array $params = null): Collection|Rocket
    {
        $contents = collect($contents)->toArray();

        return $this->pay(CallbackPlugin::class, array_merge($contents, $params ?? []));
    }

    public function success(): ResponseInterface|\Illuminate\Http\Response
    {
        return \Illuminate\Support\Facades\Response::make('1|OK', 200);
    }
}
