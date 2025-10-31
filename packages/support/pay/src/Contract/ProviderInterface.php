<?php

declare(strict_types=1);

namespace Support\Pay\Contract;

use Illuminate\Support\Collection;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Support\Pay\Rocket;

interface ProviderInterface
{
    public function pay(array|string $plugins, array $params): null|Collection|MessageInterface|Rocket;

    public function query(array $order): Collection|Rocket;

    public function cancel(array $order): Collection|Rocket;

    public function close(array $order): Collection|Rocket;

    public function refund(array $order): Collection|Rocket;

    public function callback(null|array|ServerRequestInterface $contents = null, ?array $params = null): Collection|Rocket;

    public function success(): ResponseInterface|\Illuminate\Http\Response;
}
