# 三方支付整合套件 - 快速入門

## 黑貓PAY WEB 支付插件

測試刷卡專用卡號
一次付清: 4147631000000001
分期付款: 4147632000000001
銀聯卡: 6200000000000001
卡片到期日及背面末三碼可任意填入

```php
$config = [
    'username' => '902036730001',
    'password' => '@a902036730001',
];

$result = Support\Pay\PayManager::ccat($config)->web([
    'cmd'                   => 'CocsOrderAppend',
    'cust_order_no'         => (string) time(),
    'order_amount'          => (int) 500,
    'order_detail'          => '商品訂單',
    'acquirer_type'         => 'payuni', // esun: 玉山銀行, chinatrust: 中國信託銀行, payuni: 統一金流
    'send_time'             => date('Y-m-d H:i:s'),
    'apn_url'               => 'https://laravel.com',
]);
```
