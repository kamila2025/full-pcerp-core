<?php

namespace App\Enums\Gateway;

enum GatewayMethodEnum: string
{
    case 萬事達信用卡 = 'gomypay_credit_card';
    case 萬事達3期 = 'gomypay_credit_card_3';
    case 萬事達6期 = 'gomypay_credit_card_6';
    case 萬事達9期 = 'gomypay_credit_card_9';
    case 萬事達12期 = 'gomypay_credit_card_12';
    case 萬事達18期 = 'gomypay_credit_card_18';
    case 萬事達24期 = 'gomypay_credit_card_24';
    case 萬事達30期 = 'gomypay_credit_card_30';

    case 黑貓Pay信用卡 = 'ccatpay_credit_card';
    case 黑貓Pay3期 = 'ccatpay_credit_card_3';
    case 黑貓Pay6期 = 'ccatpay_credit_card_6';
    case 黑貓Pay9期 = 'ccatpay_credit_card_9';
    case 黑貓Pay12期 = 'ccatpay_credit_card_12';
    case 黑貓Pay18期 = 'ccatpay_credit_card_18';
    case 黑貓Pay24期 = 'ccatpay_credit_card_24';
    case 黑貓Pay30期 = 'ccatpay_credit_card_30';
}
