<?php

declare(strict_types=1);

namespace Support\Pay;

use Illuminate\Support\Collection;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Support\Pay\Traits\Accessable;
use Support\Pay\Traits\Serializable;

class Rocket implements \JsonSerializable, \Serializable, \ArrayAccess
{
    use Accessable, Serializable;

    private RequestInterface|null $radar = null;

    private array $params = [];

    private Collection|null $payload = null;

    private string|null $direction = null;

    private Collection|MessageInterface|null $destination = null;

    private MessageInterface|null $destinationOrigin = null;

    public function __construct(protected $config = [])
    {
    }

    public function getConfig(): Collection
    {
        return new Collection($this->config);
    }

    public function getRadar(): ?RequestInterface
    {
        return $this->radar;
    }

    public function setRadar(?RequestInterface $radar): Rocket
    {
        $this->radar = $radar;

        return $this;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setParams(array $params): Rocket
    {
        $this->params = $params;

        return $this;
    }

    public function getPayload(): ?Collection
    {
        return $this->payload;
    }

    public function setPayload(array|Collection $payload): Rocket
    {
        if (is_array($payload)) {
            $payload = new Collection($payload);
        }

        $this->payload = $payload;

        return $this;
    }

    public function mergePayload(array $payload): Rocket
    {
        if (empty($this->payload)) {
            $this->payload = new Collection();
        }

        $this->payload = $this->payload->merge($payload);

        return $this;
    }

    public function getDirection(): ?string
    {
        return $this->direction;
    }

    public function setDirection(string $direction): Rocket
    {
        $this->direction = $direction;

        return $this;
    }

    public function getDestination(): null|Collection|MessageInterface
    {
        return $this->destination;
    }

    public function setDestination(null|Collection|MessageInterface $destination): Rocket
    {
        $this->destination = $destination;

        return $this;
    }

    public function getDestinationOrigin(): null|MessageInterface
    {
        return $this->destinationOrigin;
    }

    public function setDestinationOrigin(null|MessageInterface $destinationOrigin): Rocket
    {
        $this->destinationOrigin = $destinationOrigin;

        return $this;
    }

    public function toArray(): array
    {
        $request = $this->getRadar();

        $destination = $this->getDestinationOrigin();

        return [
            'radar' => [
                'url' => $request?->getUri()->__toString(),
                'method' => $request?->getMethod(),
                'headers' => $request?->getHeaders(),
                'body' => (string) $request?->getBody(),
            ],
            'config' => $this->getConfig()?->toArray(),
            'params' => $this->getParams(),
            'payload' => $this->getPayload()?->toArray(),
            'direction' => $this->getDirection(),
            'destination' => $this->getDestination(),
            'destination_origin' => [
                'status' => $destination instanceof ResponseInterface ? $destination->getStatusCode() : null,
                'headers' => $destination?->getHeaders(),
                'body' => (string) $destination?->getBody(),
            ],
        ];
    }
}
