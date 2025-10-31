<?php

namespace App\Events;

use App\Enums\InventoryLog\InventoryLogTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryChanged
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public InventoryLogTypeEnum $type,
        public int|string|null $variantId,
        public int|string|null $locationId,
        public int $quantity,
        public ?string $reason = '',
        public ?Model $causer = null,
        public ?Model $reference = null,
        public ?array $properties = null,
    ) {
        //
    }
}
