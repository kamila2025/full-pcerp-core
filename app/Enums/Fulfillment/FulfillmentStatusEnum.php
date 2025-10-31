<?php

namespace App\Enums\Fulfillment;

enum FulfillmentStatusEnum: string
{
    case 已送達 = 'delivered';
    case 配送中 = 'out_for_delivery';
    case 轉運中 = 'in_transit';
    case 可取貨 = 'available_for_pickup';
    case 待取貨 = 'pending_pickup';
    case 待出貨 = 'open';
    case 待辦 = 'pending';
    case 失敗 = 'failure';
    case 錯誤 = 'error';
    case 已取消 = 'cancelled';
    case 標籤已列印 = 'label_printed';
    case 標籤已購買 = 'label_purchased';
    case 嘗試交付 = 'attempted_delivery';
    case 已出貨 = 'fulfilled';
    case 已取貨 = 'successful_pickup';
    case 已送至分揀中心 = 'arrived_sorting_hub';
}
