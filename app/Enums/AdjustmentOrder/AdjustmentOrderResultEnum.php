<?php

namespace App\Enums\AdjustmentOrder;

enum AdjustmentOrderResultEnum: string
{
    case 未完成 = 'incomplete';
    case 符合 = 'match';
    case 不符合 = 'unmatch';
    case 已校正 = 'corrected';

    public function canFlowTo(self|string $next): bool
    {
        $next = is_string($next) ? self::from($next) : $next;

        return in_array($next, $this->flowTransitions());
    }

    public function flowTransitions(): array
    {
        return match ($this) {
            self::不符合 => [self::已校正],
            default => [],
        };
    }
}