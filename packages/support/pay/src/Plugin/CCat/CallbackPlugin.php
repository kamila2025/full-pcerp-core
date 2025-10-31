<?php

declare(strict_types=1);

namespace Support\Pay\Plugin\CCat;

use Support\Pay\Contract\PluginInterface;
use Support\Pay\Logger;
use Support\Pay\Rocket;

class CallbackPlugin implements PluginInterface
{
    public function assembly(Rocket $rocket, \Closure $next): Rocket
    {
        Logger::debug('[CCat][CallbackPlugin] 插件開始裝載', ['rocket' => $rocket]);

        /**
         * 解析後實際參數
         *
         * $params = [
         *     'api_id' => '902036730001',
         *     'trans_id' => '2024031800670667',
         *     'order_no' => '1710757157',
         *     'amount' => 1000,
         *     'status' => 'B',
         *     'payment_code' => 1,
         *     'payment_detail' => [
         *         'auth_code' => '000000',
         *         'auth_card_no' => '414763******0001',
         *         'pay_date' => '2024-03-18 18:20:08',
         *         'pay_amount' => 1000,
         *     ],
         *     'memo' => NULL,
         *     'expire_time' => '2024-03-18 21:19:00',
         *     'create_time' => '2024-03-18 18:19:19',
         *     'modify_time' => '2024-03-18 18:20:14',
         *     'nonce' => '1820160468',
         *     'checksum' => '2e7ffd5ae0d507944f090072ae75d14d',
         *     'print_invoice' => '0',
         *     'vehicle_type' => NULL,
         *     'vehicle_barcode' => NULL,
         *     'donate_invoice' => '0',
         *     'love_code' => NULL,
         *     'invoice_no' => NULL,
         *     'invoice_date' => NULL,
         *     'random_number' => NULL,
         *     'invoice_discount_no' => NULL,
         * ];
         */
        $params = $rocket->getParams();

        // 檢查參數
        if (!isset($params['api_id']) || !isset($params['trans_id']) || !isset($params['amount']) || !isset($params['status']) || !isset($params['nonce'])) {
            throw new \Exception('CCat 回調參數缺少 (api_id, trans_id, amount, status, nonce): ' . json_encode($params));
        }

        if ($params['checksum'] !== $this->generate($params)) throw new \Exception('CCat `checksum` 驗證簽章失敗: ' . json_encode($params));

        // 設定回應
        $rocket
            ->setPayload($params)
            ->setDestination($rocket->getPayload());

        Logger::info('[CCat][CallbackPlugin] 插件裝載完畢', ['rocket' => $rocket]);

        return $next($rocket);
    }

    protected function generate(array $params)
    {
        // 字串組合
        $str = "{$params['api_id']}:{$params['trans_id']}:{$params['amount']}:{$params['status']}:{$params['nonce']}";

        // MD5 進行加密產生雜凑值
        $str = md5($str);

        // 回傳字串
        return $str;
    }
}
