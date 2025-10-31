<?php

namespace App\Enums\PurchaseOrder;

enum PurchaseOrderStatusEnum: string
{
    case 新開單 = 'open';
    case 進貨中 = 'pending';
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
            self::新開單 => [self::進貨中, self::已完成],
            self::進貨中 => [self::已完成],
            self::已完成 => [self::已取消],
        };
    }

    public function canEdit(): bool
    {
        return match ($this) {
            self::新開單, self::進貨中 => true,
            self::已完成, self::已取消 => false,
        };
    }

    public function canDelete(): bool
    {
        return match ($this) {
            self::新開單, self::進貨中 => true,
            default => false,
        };
    }
}
