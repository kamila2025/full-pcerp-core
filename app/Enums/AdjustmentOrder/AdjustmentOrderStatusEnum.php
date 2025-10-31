<?php

namespace App\Enums\AdjustmentOrder;

enum AdjustmentOrderStatusEnum: string
{
    case 新開單 = 'open';
    case 盤點中 = 'in_progress';
    case 已完成 = 'completed';
    case 已取消 = 'cancelled';

    public function canFlowTo(self|string $next): bool
    {
        $next = is_string($next) ? self::from($next) : $next;

        return in_array($next, $this->flowTransitions());
    }

    public function flowTransitions(): array
    {
        return match ($this) {
            self::新開單 => [self::盤點中, self::已完成],
            self::盤點中 => [self::已完成],
        };
    }

    public function canEdit(): bool
    {
        return match ($this) {
            self::新開單, self::盤點中 => true,
            default => false,
        };
    }

    public function canDelete(): bool
    {
        return match ($this) {
            self::新開單, self::盤點中 => true,
            default => false,
        };
    }
}