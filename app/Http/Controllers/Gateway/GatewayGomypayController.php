<?php

namespace App\Http\Controllers\Gateway;

use App\Enums\Transaction\TransactionStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GatewayGomypayController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        Log::debug('[Gomypay][CallbackPlugin] 收到回調', [
            'request' => $request->all(),
        ]);

        try {
            $params = $request->all();

            // 檢查參數
            if (!isset($params['result']) || !isset($params['e_orderno']) || !isset($params['e_no']) || !isset($params['e_money']) || !isset($params['str_no']) || !isset($params['str_check'])) {
                throw new \Exception('回調參數缺少 (result, e_orderno, e_no, e_money, str_no, str_check): ' . json_encode($params));
            }

            // 簽章字串
            $signToString = $params['result'] . $params['e_orderno'] .  $params['e_no']  . $params['e_money'] . $params['str_no'] . env('GOMYPAY_STR_CHECK');

            // 驗證簽章
            if (md5($signToString) !== $params['str_check']) throw new \Exception('Gomypay `str_check` 驗證簽章失敗: ' . json_encode($params));

            if ($params['result'] !== '1') throw new \Exception('Gomypay `result` 驗證失敗: ' . json_encode($params));

            $transaction = Transaction::query()
                ->with('order')
                ->where('status', '<>', TransactionStatusEnum::已付款)
                ->where('number', $params['e_orderno'])
                ->first();

            if (!$transaction) throw new \Exception('找不到對應的交易或交易已處理: ' . $params['e_orderno']);

            // 更新交易狀態
            $transaction->update([
                'status' => TransactionStatusEnum::已付款,
                'paid_at' => now(),
                'fee' => $params['e_outlay'],
            ]);

            Log::info('[Gomypay][CallbackPlugin] 交易更新成功', [
                'transaction_id' => $transaction->id,
                'order_number' => $params['e_orderno'],
            ]);

            return 'OK';
        } catch (\Throwable $e) {
            Log::error('[Gomypay][CallbackPlugin] 錯誤', [
                'request' => $request->all(),
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return 'Error: ' . $e->getMessage();
        }
    }
}
