<?php

declare(strict_types=1);

namespace Support\Pay\Shortcut\CCat;

use Support\Pay\Contract\ShortcutInterface;
use Support\Pay\Plugin\CCat\Pay\RequestTokenPlugin;
use Support\Pay\Plugin\CCat\WebPayPlugin;

class WebShortcut implements ShortcutInterface
{
    public function getPlugins(array $params): array
    {
        return [
            RequestTokenPlugin::class,
            WebPayPlugin::class,
        ];
    }
}
