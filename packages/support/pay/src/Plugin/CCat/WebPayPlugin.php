<?php

declare(strict_types=1);

namespace Support\Pay\Plugin\CCat;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use Support\Pay\Contract\PluginInterface;
use Support\Pay\Logger;
use Support\Pay\PayManager;
use Support\Pay\Rocket;

class WebPayPlugin implements PluginInterface
{
    public const URL = [
        PayManager::MODE_NORMAL    => 'https://cocs.4128888card.com.tw/api/Collect',
        PayManager::MODE_SANDBOX   => 'http://test.4128888card.com.tw/app/api/Collect',
        PayManager::MODE_SERVICE   => 'http://test.4128888card.com.tw/app/api/Collect',
    ];

    public function assembly(Rocket $rocket, \Closure $next): Rocket
    {
        Logger::debug('[CCat][WebPayPlugin] 插件開始裝載', ['rocket' => $rocket]);

        $params = collect($rocket->getParams());

        if (!$cmd = $params->get('cmd')) throw new \Exception('CCat `cmd` is params required');

        if (!$order = $params->get('cust_order_no')) throw new \Exception('CCat `cust_order_no` is params required');

        if (!$amount = $params->get('order_amount')) throw new \Exception('CCat `order_amount` is params required');

        if (!$resultUrl = $params->get('apn_url')) throw new \Exception('CCat `apn_url` is params required');

        if ($cmd === 'CvsOrderAppend' && !$params->has('payer_name')) throw new \Exception('CCat `payer_name` is params required');

        if ($cmd === 'CvsOrderAppend' && !$params->has('payer_address')) throw new \Exception('CCat `payer_address` is params required');

        if ($cmd === 'CvsOrderAppend' && !$params->has('payer_mobile')) throw new \Exception('CCat `payer_mobile` is params required');

        if ($cmd === 'CvsOrderAppend' && !$params->has('payer_email')) throw new \Exception('CCat `payer_email` is params required');

        if ($cmd === 'CvsOrderAppend' && !$params->has('payment_type')) throw new \Exception('CCat `payment_type` is params required');

        // 傳遞參數
        $rocket->setPayload([
            'cmd'                   => $cmd,                                                            // CocsOrderAppend: COCS 線上刷卡, CvsOrderAppend: CVS 代收代付
            'cust_id'               => $rocket->getConfig()->get('username'),
            'cust_order_no'         => $order,
            'order_amount'          => (int) $amount,
            'order_detail'          => $params->get('order_detail', "商品訂單 #$order"),                // 訂單/商品明細
            'acquirer_type'         => $params->get('acquirer_type', 'payuni'),                         // esun: 玉山銀行, chinatrust: 中國信託銀行, payuni: 統一金流
            'limit_product_id'      => $params->get('limit_product_id'),                                // 限制產品 ID
            'send_time'             => \Carbon\Carbon::now('Asia/Taipei')->rawFormat('Y-m-d H:i:s'),    // 傳送時間，必須為傳送時之最新時間，格式為 yyyy-MM-dd HH:mm:ss，例如：2017-07-18 07:17:25
            'apn_url'               => $resultUrl,                                                      // APN 指定傳送網址
            'success_url'           => $params->get('success_url'),                                     // 訂單授權成功指定回傳 URL
            //
            // 'cmd'                   => 'CvsOrderAppend',
            // 'cust_id'               => $rocket->getConfig()->get('username'),
            // 'cust_order_no'         => $order,
            // 'order_amount'          => (int) $amount,
            // 'expire_date'           => date('Y-m-d', strtotime("+1 day")),      // 繳費到期日(YYYY-MM-DD) (必填)
            // 'payer_name'            => $payerName,                              // 繳款人姓名 (必填)
            // 'payer_postcode'        => $params->get('payer_postcode'),          // 繳款人郵遞區號
            // 'payer_address'         => $payerAddress,                           // 繳款人地址 (必填)
            // 'payer_mobile'          => $payerMobile,                            // 繳款人手機號碼 (必填)
            // 'payer_email'           => $payerEmail,                             // 繳款人電子郵件 (必填)
            // 'payment_type'          => $paymentType,                            // 付款方式 (必填) (0: ibon 繳費, 1: ATM 銀行轉帳, 2: 三段式條碼, 9: 三段式條碼(中信即時繳款通知，僅支援 7-11、全家超商繳費)
            // 'payment_acquirerType'  => $params->get('payment_acquirerType'),    // 收單行 (0: 玉山銀行, 1: 中國信託銀行)
            // 'apn_url'               => $resultUrl,
            // 'order_detail'          => '商品訂單 #' . $order,                   // 繳款單/商品明細；若有金流服務有開啟 PDF 綜合繳款單功能，為避免跑版，限制只能輸入 150 個字。
        ]);

        // 送出請求
        $response = (new \GuzzleHttp\Client())->sendRequest((new \GuzzleHttp\Psr7\Request(
            'POST',
            self::URL[$rocket->getConfig()->get('mode', PayManager::MODE_SANDBOX)],
            [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $rocket->getDestination()->dot()->get('access_token'),
            ],
            $rocket->getPayload()->toJson()
        )));

        // 設定回應
        $rocket
            ->setDestinationOrigin($response)
            ->setDestination(new Collection($data = json_decode($response->getBody()->getContents(), true)))
            ->setPayload(new Collection($data));

        // 如果為 CVS 代收代付
        if (isset($data['status']) && $data['status'] === 'OK' && $cmd === 'CvsOrderAppend') {
            // 建立 HTML
            $response = $this->buildHtml(route('payment.rule'), $rocket->getPayload());

            // 設定回應
            $rocket->setDestination($response);
        }

        Logger::info('[CCat][WebPayPlugin] 插件裝載完畢', ['rocket' => $rocket]);

        return $next($rocket);
    }

    protected function buildHtml(string $endpoint, Collection $payload): Response
    {
        $sHtml = "<form id='pay_submit' name='pay_submit' action='" . $endpoint . "' method='POST'>";
        foreach ($payload->all() as $key => $val) {
            if (is_array($val)) continue;
            $val = str_replace("'", '&apos;', strval($val));
            $sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }
        $sHtml .= "<input type='submit' value='ok' style='display:none;'></form>";
        $sHtml .= "<script>document.forms['pay_submit'].submit();</script>";

        return new Response(200, [], $sHtml);
    }
}
