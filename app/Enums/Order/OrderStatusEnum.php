<?php

namespace App\Enums\Order;

enum OrderStatusEnum: string
{
    case 草稿 = 'draft';
    case 開啟 = 'open';
    case 取消 = 'cancelled';
    case 封存 = 'archived';
    case 刪除 = 'deleted';

    public function canEdit(): bool
    {
        return match ($this) {
            self::草稿 => true,
            self::開啟 => true,
            default => false,
        };
    }

    public function canDelete(): bool
    {
        return match ($this) {
            self::草稿 => true,
            self::開啟 => true,
            default => false,
        };
    }
}
