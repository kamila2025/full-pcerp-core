<?php

namespace App\Console\Commands;

use App\Enums\Fulfillment\FulfillmentServiceEnum;
use App\Enums\Fulfillment\FulfillmentStatusEnum;
use App\Models\Fulfillment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchBlackCatLogisticsStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logistics:blackcat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Fulfillment::query()
            ->where('service', FulfillmentServiceEnum::黑貓宅急便)
            ->whereNotNull('tracking_number')
            ->chunk(10, function ($fulfillments) {
                try {
                    $baseUrl = boolval(env('CAT_TEST_MODE')) ? 'https://egs.suda.com.tw:8443/api/Egs/OBTStatus' : 'https://api.suda.com.tw/api/Egs/OBTStatus';

                    $response = Http::asJson()->post($baseUrl, [
                        'CustomerId'        => env('CAT_CUSTOMER_ID'),
                        'CustomerToken'     => env('CAT_TOKEN'),
                        'OBTNumbers'        => $fulfillments->pluck('tracking_number'),
                    ]);

                    if ($response->json('IsOK') !== 'Y') throw new \Exception($response->json('Message', '查詢失敗'));

                    foreach ($response->json('Data.OBTs', []) as $number) {
                        if ($number['StatusId'] !== '301') continue;

                        Fulfillment::query()
                            ->where('service', FulfillmentServiceEnum::黑貓宅急便)
                            ->where('tracking_number', $number['OBTNumber'])
                            ->update([
                                'message'   => $number['StatusName'],
                                'status'    => FulfillmentStatusEnum::已出貨,
                            ]);
                    }
                } catch (\Exception $e) {
                    Log::error('FetchBlackCatLogisticsStatus', [
                        'message' => $e->getMessage(),
                        'trace'   => $e->getTraceAsString(),
                        'data'    => $fulfillments->toArray(),
                    ]);
                }
            });
    }
}
