<?php

namespace Support\Pay;

/**
 * @method static Provider\CCat ccat(array $config = [])
 */
class PayManager
{
    /**
     * 正常模式.
     */
    public const MODE_NORMAL = 'normal';

    /**
     * 沙箱模式.
     */
    public const MODE_SANDBOX = 'sandbox';

    /**
     * 服務商模式.
     */
    public const MODE_SERVICE = 'service';

    /**
     * @var string[]
     */
    public static array $service = [
        'ccat'      => Provider\CCat::class,
    ];

    public static function __callStatic(string $service, array $parameters): mixed
    {
        if (isset(self::$service[$service]) && class_exists($class = self::$service[$service])) {
            return new $class(...$parameters);
        }

        throw new \InvalidArgumentException("Driver [$service] not supported.");
    }
}
