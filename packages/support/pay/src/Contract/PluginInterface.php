<?php

declare(strict_types=1);

namespace Support\Pay\Contract;

use Support\Pay\Rocket;

interface PluginInterface
{
    public function assembly(Rocket $rocket, \Closure $next): Rocket;
}
