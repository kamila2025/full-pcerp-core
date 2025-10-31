<?php

namespace App\Enums\InventoryLog;

enum InventoryLogTypeEnum: string
{
    case 商品建立 = 'product_created';
    case 商品更新 = 'product_updated';
    case 規格刪除 = 'variant_deleted';
    case 批量商品更新 = 'product_bulk_updated';
    case 庫存建立 = 'inventory_created'; // TODO: 之後不會有了
    case 庫存更新 = 'inventory_updated';
    case 批量庫存更新 = 'inventory_bulk_updated';
    case 商品交易 = 'product_transaction';
    case 編輯商品交易 = 'product_transaction_edit';
    case 刪除商品交易 = 'product_transaction_deleted';
    case 盤點單校正 = 'inventory_adjustment_corrected';
    case 調撥單入庫 = 'inventory_transfer_in';
    case 調撥單移出 = 'inventory_transfer_out';
    case 取消調撥單 = 'inventory_transfer_cancel';
    case 調撥單歸還 = 'inventory_transfer_return';
    case 進貨單入庫 = 'purchase_order_completed';
    case 進貨單取消 = 'purchase_order_cancelled';
    case 退貨單出庫 = 'purchase_return_order_completed';
}
