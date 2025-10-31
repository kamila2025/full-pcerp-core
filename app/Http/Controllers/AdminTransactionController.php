<?php

namespace App\Http\Controllers;

use App\Enums\Gateway\GatewayMethodEnum;
use App\Enums\Gateway\GatewayTypeEnum;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;

class AdminTransactionController extends Controller
{
    public function __invoke(Request $request, string $shortCode)
    {
        $transaction = Transaction::query()->where('short_code', $shortCode)->firstOrFail();

        try {
            if ($transaction->gateway_type === GatewayTypeEnum::黑貓Pay) {
                $config = [
                    'username' => env('CCAT_USERNAME'),
                    'password' => env('CCAT_PASSWORD'),
                ];

                $result = \Support\Pay\PayManager::ccat($config)->web([
                    'cmd'                   => 'CocsOrderAppend',
                    'cust_order_no'         => $transaction->number,
                    'order_amount'          => $transaction->amount,
                    'order_detail'          => '電腦組裝',
                    'limit_product_id'      => match ($transaction->gateway_method) {
                        GatewayMethodEnum::黑貓Pay信用卡 => 'payuni.normal',
                        GatewayMethodEnum::黑貓Pay3期 => 'payuni.m3',
                        GatewayMethodEnum::黑貓Pay6期 => 'payuni.m6',
                        GatewayMethodEnum::黑貓Pay9期 => 'payuni.m9',
                        GatewayMethodEnum::黑貓Pay12期 => 'payuni.m12',
                        GatewayMethodEnum::黑貓Pay18期 => 'payuni.m18',
                        GatewayMethodEnum::黑貓Pay24期 => 'payuni.m24',
                        GatewayMethodEnum::黑貓Pay30期 => 'payuni.m30',
                    },
                    'apn_url'               => route('webhook.ccat'),
                ]);

                return redirect($result['url']);
            }

            $baseUrl = match ($transaction->gateway_type) {
                GatewayTypeEnum::萬事達 => boolval(env('GOMYPAY_TEST_MODE')) ? 'https://n.gomypay.asia/TestShuntClass.aspx' : 'https://n.gomypay.asia/ShuntClass.aspx',
                default => throw new \Exception('不支援的付款方式'),
            };

            $payload = collect([
                'Send_Type'     => 0,
                'Pay_Mode_No'   => 2,
                'CustomerId'    => env('GOMYPAY_CUSTOMER_ID'),
                'Order_No'      => $transaction->number,
                'Amount'        => $transaction->amount,
                'TransCode'     => '00',
                // 'Buyer_Name'    => '王小明',
                // 'Buyer_Telm'    => '0910000000',
                // 'Buyer_Mail'    => 'admin@gmail.com',
                'Buyer_Memo'    => '電腦組裝',
                'Callback_Url'  => route('webhook.gomypay'),
                'TransMode'     => match ($transaction->gateway_method) {  // 交易模式一般請填(1)、分期請填(2)
                    GatewayMethodEnum::萬事達信用卡 => 2,
                    default => 1,
                },
                'Installment'   => match ($transaction->gateway_method) {
                    GatewayMethodEnum::萬事達3期 => 3,
                    GatewayMethodEnum::萬事達6期 => 6,
                    GatewayMethodEnum::萬事達9期 => 9,
                    GatewayMethodEnum::萬事達12期 => 12,
                    GatewayMethodEnum::萬事達18期 => 18,
                    GatewayMethodEnum::萬事達24期 => 24,
                    GatewayMethodEnum::萬事達30期 => 30,
                    default => 0,
                },
            ]);

            return $this->buildHtml($baseUrl, $payload);
        } catch (\Exception $e) {
            return Response::make('Error: ' . $e->getMessage(), 500);
        }
    }

    protected function buildHtml(string $endpoint, Collection $payload)
    {
        $sHtml = "<form id='pay_submit' name='pay_submit' action='" . $endpoint . "' method='POST'>";
        foreach ($payload->all() as $key => $val) {
            if (is_array($val)) continue;
            $val = str_replace("'", '&apos;', strval($val));
            $sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }
        $sHtml .= "<input type='submit' value='ok' style='display:none;'></form>";
        $sHtml .= "<script>document.forms['pay_submit'].submit();</script>";

        return Response::make($sHtml, 200);
    }
}
