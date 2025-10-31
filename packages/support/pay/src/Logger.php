<?php

declare(strict_types=1);

namespace Support\Pay;

/**
 * @method static void emergency($message, array $context = [])
 * @method static void alert($message, array $context = [])
 * @method static void critical($message, array $context = [])
 * @method static void error($message, array $context = [])
 * @method static void warning($message, array $context = [])
 * @method static void notice($message, array $context = [])
 * @method static void info($message, array $context = [])
 * @method static void debug($message, array $context = [])
 * @method static void log($message, array $context = [])
 */
class Logger
{
    public static function __callStatic(string $method, array $args): void
    {
        $class = app('log');

        if ($class instanceof \Psr\Log\LoggerInterface) {
            $class->build(['driver' => 'daily', 'path' => storage_path('logs/pay/laravel.log')])->{$method}(...$args);

            return;
        }

        throw new \Exception('Logger must be an instance of Psr\Log\LoggerInterface.');
    }
}
