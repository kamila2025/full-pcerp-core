<?php

namespace App\Enums;

enum PermissionNameEnum: string
{
    case 所有權限       = 'manage';

    /**
     * 其他
     */
    case 後台管理       = 'admin';
    // case 前台管理       = 'front';
    case 分期試算       = 'installments';

    /**
     * 訂單管理
     */
    case 訂單介面       = 'orders.interface';
    case 所有訂單       = 'orders';
    case 收款明細       = 'transactions';
    case 出貨明細       = 'fulfillments';

    /**
     * 商品管理
     */
    case 商品介面       = 'products.interface';
    case 所有商品       = 'products';
    case 商品分類       = 'categories';
    case 商品品牌       = 'brands';
    case 庫存管理       = 'inventories';

    /**
     * 客戶管理
     */
    case 客戶介面       = 'customers.interface';
    case 所有客戶       = 'customers';

    /**
     * 門市管理
     */
    case 門市介面       = 'stores.interface';
    case 進貨管理       = 'purchase-orders';
    case 調撥管理       = 'transfer-orders';
    case 盤點管理       = 'adjustment-orders';

    /**
     * 分析報表
     */
    case 分析介面       = 'report.interface';
    case 銷售報告       = 'analytics';
    case 庫存分析       = 'inventory';

    /**
     * 設定
     */
    case 設定介面       = 'settings.interface';
    case 模板設定       = 'templates';
    case 收款設定       = 'gateways';
    case 物流設定       = 'logistics';
    case 地址設定       = 'locations';
    case 通知設定       = 'channels';
    case 員工設定       = 'users';
    case 角色權限       = 'roles';
    // case 參數設定       = 'settings';

    /**
     * Beta 功能
     */
    // case 批發設定       = 'wholesales';
}
