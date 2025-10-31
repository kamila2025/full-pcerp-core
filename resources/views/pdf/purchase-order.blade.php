<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{ $purchaseOrder['type'] == \App\Enums\PurchaseOrder\PurchaseOrderTypeEnum::進貨 ? '進貨單' : '退貨單' }}</title>
    <style>
        @font-face {
            font-family: 'Noto Sans TC';
            src: url('{{ asset("fonts/NotoSansTC-Regular.ttf") }}') format('truetype');
            font-weight: normal;
        }

        @font-face {
            font-family: 'Noto Sans TC';
            src: url('{{ asset("fonts/NotoSansTC-Bold.ttf") }}') format('truetype');
            font-weight: bold;
        }

        * {
            word-wrap: break-word;
            white-space: normal;
        }

        @page {
            margin: 120px 40px 80px 40px;
        }

        body {
            font-family: 'Noto Sans TC', sans-serif;
            font-size: 12px;
            color: #333;
        }

        header,
        footer {
            position: fixed;
            left: 0;
            right: 0;
        }

        header {
            top: -80px;
            height: 60px;
        }

        footer {
            bottom: -50px;
            height: 30px;
            text-align: right;
            font-size: 11px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table td,
        table th {
            padding: 6px;
            border: 1px solid #ddd;
        }

        table th {
            background: #f9f9f9;
            text-align: center;
            font-weight: bold;
        }

        .info-table td {
            border: none;
            padding: 4px 6px;
        }

        .no-border {
            border: none !important;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .bg-gray {
            background-color: #f5f5f5;
        }
    </style>
</head>

<body>
    <header>
        <table style="width: 100%; border: none;">
            <tr>
                <!-- 左邊 -->
                <td style="width: 70%; vertical-align: top; border: none;">
                    <h2 style="margin: 0 0 8px 0;">{{ $purchaseOrder['type'] == \App\Enums\PurchaseOrder\PurchaseOrderTypeEnum::進貨 ? '進貨單' : '退貨單' }}</h2>
                </td>
                <!-- 右邊 -->
                <td style="width: 30%; text-align: right; vertical-align: top; border: none;">
                    <img src="images/logo/company-logo.png" alt="Logo" style="width: 100px;">
                </td>
            </tr>
        </table>
    </header>

    <main>
        <!-- 基本資訊 -->
        <table class="info-table" style="margin-bottom: 20px;">
            <tr>
                <td style="width: 15%; font-weight: bold;">單據編號：</td>
                <td style="width: 35%;">{{ $purchaseOrder['order_number'] }}</td>
                <td style="width: 15%; font-weight: bold;">建立日期：</td>
                <td style="width: 35%;">{{ \Carbon\Carbon::parse($purchaseOrder['created_at'])->format('Y-m-d H:i') }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">單據狀態：</td>
                <td>{{ $purchaseOrder['status'] }}</td>
                <td style="font-weight: bold;">到貨狀態：</td>
                <td>{{ $purchaseOrder['arrival_status'] ?? '-' }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">分店：</td>
                <td>{{ $purchaseOrder['location']['name'] ?? '-' }}</td>
                <td style="font-weight: bold;">進貨人員：</td>
                <td>{{ $purchaseOrder['user']['name'] ?? '-' }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">入庫人員：</td>
                <td>{{ $purchaseOrder['executorUser']['name'] ?? '-' }}</td>
                <td style="font-weight: bold;">自訂單號：</td>
                <td>{{ $purchaseOrder['custom_number'] ?? '-' }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">預定到貨：</td>
                <td>{{ $purchaseOrder['scheduled_time'] ? \Carbon\Carbon::parse($purchaseOrder['scheduled_time'])->format('Y-m-d') : '-' }}</td>
                <td style="font-weight: bold;">完成日期：</td>
                <td>{{ $purchaseOrder['actual_time'] ? \Carbon\Carbon::parse($purchaseOrder['actual_time'])->format('Y-m-d H:i') : '-' }}</td>
            </tr>
        </table>

        <!-- 商品明細 -->
        <table style="margin-bottom: 20px;">
            <thead>
                <tr>
                    @if(isset($hideAmounts) && $hideAmounts)
                        <th style="width: 35%;">商品名稱</th>
                        <th style="width: 30%;">款式</th>
                        <th style="width: 15%;">數量</th>
                        <th style="width: 20%;">用途/備註</th>
                    @else
                        <th style="width: 25%;">商品名稱</th>
                        <th style="width: 20%;">款式</th>
                        <th style="width: 10%;">數量</th>
                        <th style="width: 15%;">銷售價格</th>
                        <th style="width: 15%;">成本價格</th>
                        <th style="width: 15%;">小計</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($purchaseOrder['items'] as $item)
                    <tr>
                        <td>
                            {{ $item['product_name'] }}
                            @if(isset($groupSameItems) && $groupSameItems && isset($item['order_items_count']) && $item['order_items_count'] > 0)
                                <br><small style="color: #666; font-size: 10px;">(拋轉: {{ $item['order_items_count'] }})</small>
                            @endif
                        </td>
                        <td>{{ $item['variant_name'] }}</td>
                        <td class="text-center">{{ $item['quantity'] }}</td>
                        @if(isset($hideAmounts) && $hideAmounts)
                            <td>{{ $item['purpose'] ?? '-' }}</td>
                        @else
                            <td class="text-right">NT$ {{ number_format($item['price']) }}</td>
                            <td class="text-right">NT$ {{ number_format($item['cost_price']) }}</td>
                            <td class="text-right">NT$ {{ number_format($item['total_cost']) }}</td>
                        @endif
                    </tr>
                @endforeach
                
                <!-- 總計行 -->
                @if(!(isset($hideAmounts) && $hideAmounts))
                <tr class="bg-gray">
                    <td colspan="2" class="font-bold text-center">總計</td>
                    <td class="text-center font-bold">{{ $purchaseOrder['items']->sum('quantity') }}</td>
                    <td></td>
                    <td></td>
                    <td class="text-right font-bold">NT$ {{ number_format($purchaseOrder['items']->sum('total_cost')) }}</td>
                </tr>
                
                @if($purchaseOrder['other_fee'] > 0)
                <tr>
                    <td colspan="5" class="text-right font-bold">其他費用：</td>
                    <td class="text-right">NT$ {{ number_format($purchaseOrder['other_fee']) }}</td>
                </tr>
                <tr class="bg-gray">
                    <td colspan="5" class="text-right font-bold">總金額：</td>
                    <td class="text-right font-bold">NT$ {{ number_format($purchaseOrder['items']->sum('total_cost') + $purchaseOrder['other_fee']) }}</td>
                </tr>
                @endif
                @else
                <tr class="bg-gray">
                    <td colspan="2" class="font-bold text-center">總計</td>
                    <td class="text-center font-bold">{{ $purchaseOrder['items']->sum('quantity') }}</td>
                    <td></td>
                </tr>
                @endif
            </tbody>
        </table>

        <!-- 備註區 -->
        @if($purchaseOrder['remark'])
        <div style="margin-top: 30px;">
            <table class="info-table">
                <tr>
                    <td style="width: 10%; font-weight: bold; vertical-align: top;">備　　註：</td>
                    <td style="width: 90%;">{{ $purchaseOrder['remark'] }}</td>
                </tr>
            </table>
        </div>
        @endif

        <!-- 簽章區 -->
        <div style="margin-top: 50px;">
            <table class="info-table">
                <!-- 第一行：進貨人員 + 入庫人員 -->
                <tr>
                    <td style="width: 50%;">
                        <p>{{ $purchaseOrder['type'] == \App\Enums\PurchaseOrder\PurchaseOrderTypeEnum::進貨 ? '進貨' : '退貨' }}人員：{{ $purchaseOrder['user']['name'] ?? '　' }}</p>
                        <p>簽名：＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿</p>
                    </td>
                    <td style="width: 50%; text-align: right;">
                        <p>入庫人員：{{ $purchaseOrder['executorUser']['name'] ?? '　' }}</p>
                        <p>簽名：＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿</p>
                    </td>
                </tr>
            </table>
        </div>

    </main>

    <footer>
        <!-- <div>列印時間：{{ now()->format('Y-m-d H:i:s') }} | 第 {PAGE_NUM} 頁，共 {PAGE_COUNT} 頁</div> -->
    </footer>

</body>

</html>