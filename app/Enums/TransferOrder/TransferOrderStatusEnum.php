<?php

namespace App\Enums\TransferOrder;

enum TransferOrderStatusEnum: string
{
    case 新開單 = 'open';
    case 調撥中 = 'transferring';
    case 已入庫 = 'completed';
    case 已取消 = 'cancelled';

    public function canFlowTo(self|string $next): bool
    {
        $next = is_string($next) ? self::from($next) : $next;

        return in_array($next, $this->flowTransitions());
    }

    public function flowTransitions(): array
    {
        return match ($this) {
            self::新開單 => [self::調撥中],
            self::調撥中 => [self::已入庫],
            self::已入庫 => [self::已取消],
        };
    }

    public function canEdit(): bool
    {
        return match ($this) {
            self::新開單 => true,
            default => false,
        };
    }

    public function canDelete(): bool
    {
        return match ($this) {
            self::新開單 => true,
            default => false,
        };
    }
}
