<?php

namespace App\Enums\Gateway;

enum GatewayTypeEnum: string
{
    case 萬事達 = 'gomypay';
    case 黑貓Pay = 'ccatpay';
    case 銀行轉帳 = 'bank-transfer';
    case 分期付款 = 'installment';
    case 自訂付款 = 'custom';

    public function subGateways(): array
    {
        return match ($this) {
            self::萬事達 => [
                ['gateway_method' => GatewayMethodEnum::萬事達信用卡->value, 'title' => '萬事達信用卡'],
                ['gateway_method' => GatewayMethodEnum::萬事達3期->value, 'title' => '萬事達3期'],
                ['gateway_method' => GatewayMethodEnum::萬事達6期->value, 'title' => '萬事達6期'],
                ['gateway_method' => GatewayMethodEnum::萬事達9期->value, 'title' => '萬事達9期'],
                ['gateway_method' => GatewayMethodEnum::萬事達12期->value, 'title' => '萬事達12期'],
                ['gateway_method' => GatewayMethodEnum::萬事達18期->value, 'title' => '萬事達18期'],
                ['gateway_method' => GatewayMethodEnum::萬事達24期->value, 'title' => '萬事達24期'],
                ['gateway_method' => GatewayMethodEnum::萬事達30期->value, 'title' => '萬事達30期'],
            ],
            self::黑貓Pay => [
                ['gateway_method' => GatewayMethodEnum::黑貓Pay信用卡->value, 'title' => '黑貓Pay信用卡'],
                ['gateway_method' => GatewayMethodEnum::黑貓Pay3期->value, 'title' => '黑貓Pay3期'],
                ['gateway_method' => GatewayMethodEnum::黑貓Pay6期->value, 'title' => '黑貓Pay6期'],
                ['gateway_method' => GatewayMethodEnum::黑貓Pay9期->value, 'title' => '黑貓Pay9期'],
                ['gateway_method' => GatewayMethodEnum::黑貓Pay12期->value, 'title' => '黑貓Pay12期'],
                ['gateway_method' => GatewayMethodEnum::黑貓Pay18期->value, 'title' => '黑貓Pay18期'],
                ['gateway_method' => GatewayMethodEnum::黑貓Pay24期->value, 'title' => '黑貓Pay24期'],
                ['gateway_method' => GatewayMethodEnum::黑貓Pay30期->value, 'title' => '黑貓Pay30期'],
            ],
            default => [],
        };
    }
}
