<?php

namespace App\Http\Controllers\Gateway;

use App\Enums\Transaction\TransactionStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GatewayCatPayController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        Log::debug('[CatPay][CallbackPlugin] 收到回調', [
            'request' => $request->all(),
        ]);

        try {
            $params = $request->all();

            // 檢查參數
            if (!isset($params['api_id']) || !isset($params['trans_id']) || !isset($params['amount']) || !isset($params['status']) || !isset($params['nonce'])) {
                throw new \Exception('回調參數缺少 (result, e_orderno, e_no, e_money, str_no, str_check): ' . json_encode($params));
            }

            // 簽章字串
            $signToString = $params['api_id'] . ':' . $params['trans_id'] . ':' . $params['amount'] . ':' . $params['status'] . ':' . $params['nonce'];

            // 驗證簽章
            if (md5($signToString) !== $params['checksum']) throw new \Exception('CatPay `checksum` 驗證簽章失敗: ' . json_encode($params));

            if ($params['status'] !== 'B') throw new \Exception('CatPay `status` 驗證失敗: ' . json_encode($params));

            $transaction = Transaction::query()
                ->with('order')
                ->where('status', '<>', TransactionStatusEnum::已付款)
                ->where('number', $params['order_no'])
                ->first();

            if (!$transaction) throw new \Exception('找不到對應的交易或交易已處理: ' . $params['order_no']);

            // 更新交易狀態
            $transaction->update([
                'status' => TransactionStatusEnum::已付款,
                'paid_at' => now(),
            ]);

            Log::info('[CatPay][CallbackPlugin] 交易更新成功', [
                'transaction_id' => $transaction->id,
                'order_number' => $params['order_no'],
            ]);

            return 'OK';
        } catch (\Throwable $e) {
            Log::error('[CatPay][CallbackPlugin] 錯誤', [
                'request' => $request->all(),
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return 'Error: ' . $e->getMessage();
        }
    }

    public function success(Request $request)
    {
        return view('gateway.ccat.success', [
            'order_amount' => $request->get('order_amount'),
            'acquire_time' => $request->get('acquire_time'),
        ]);
    }
}
