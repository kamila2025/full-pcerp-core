<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Receipt</title>
    <style>
        @font-face {
            font-family: 'Noto Sans TC';
            src: url('{{ asset('fonts/NotoSansTC-Regular.ttf') }}') format('truetype');
            font-weight: normal;
        }

        @font-face {
            font-family: 'Noto Sans TC';
            src: url('{{ asset('fonts/NotoSansTC-Bold.ttf') }}') format('truetype');
            font-weight: bold;
        }

        * {
            word-wrap: break-word;
            white-space: normal;
        }

        h5 {
            counter-reset: page;
        }

        .page-number:after {
            /* counter-increment: pages; */
            /* content: counter(pages); */
        }

        .page-number:before {
            /* counter-increment: pages; */
            /* content: "Page " counter(page) "of " counter(pages); */
        }

        footer {
            position: fixed;
            bottom: 0px;
        }

        footer:before {
            content: counter(page) "/" counter(pages);
        }

        @page {
            /* size: A4; */
            margin: 120px 40px 80px 40px;

            /* @top-right {
                color: white;
                content: "第 " counter(page) " 頁，共 " counter(pages) " 頁";
            }

            @bottom {
                color: white;
                content: "Page " counter(page) " of " counter(pages);
            } */

            @bottom {
                content: "Chapter #" counter(chapter);
            }
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
        }

        table th {
            background: #f9f9f9;
            text-align: left;
        }
    </style>
</head>

<body>
    <header>
        <table style="width: 100%; ">
            <tr>
                <!-- 左邊 -->
                <td style="width: 70%; vertical-align: top;">
                    <h2 style="margin: 0 0 8px 0;">報價單</h2>
                </td>
                <!-- 右邊 -->
                <td style="width: 30%; text-align: right; vertical-align: top;">
                    <img src="{{ asset('images/logo/company-logo.png') }}" alt="Logo" style="width: 100px;">
                </td>
            </tr>
        </table>
    </header>

    <main>
        <div style="font-size: 12px; margin-left: 6px;">
            <div>
                <span style="vertical-align: top; display: inline-block; width: 80px; font-weight: bold;">訂單編號</span>
                {{ $order['order_number'] }}
            </div>
            <div>
                <span style="vertical-align: top; display: inline-block; width: 80px; font-weight: bold;">訂單日期</span>
                {{ $order['created_at'] }}
            </div>
            {{-- <div>
                <span style="vertical-align: top; display: inline-block; width: 80px; font-weight: bold;">付款方式</span>
                信用卡 - 9665
            </div> --}}
        </div>

        <table>
            <tr>
                <td>
                    <strong>店家資訊</strong><br>
                    <strong>{{ $order['location']['name'] ?? '' }}</strong><br>
                    {{ $order['location']['company'] ?? '' }}<br>
                    {{ $order['location']['address1'] ?? '' }}<br>
                    {{ $order['location']['email'] ?? '' }}<br>
                    {{ $order['location']['phone'] ?? '' }}<br>
                </td>
                <td>
                    <strong>帳單資訊</strong><br>
                    {{ $order['shippingAddress']['full_name'] ?? $order['pickupAddress']['full_name'] ?? '' }}<br>
                    {{ $order['shippingAddress']['company'] ?? $order['pickupAddress']['company'] ?? '' }}<br>
                    {{ $order['shippingAddress']['address1'] ?? $order['pickupAddress']['address1'] ?? '' }}<br>
                    {{-- {{ $order['shippingAddress']['country'] ?? $order['pickupAddress']['country'] ?? '' }}<br> --}}
                    {{ $order['shippingAddress']['email'] ?? $order['pickupAddress']['email'] ?? '' }}<br>
                    {{ $order['shippingAddress']['phone'] ?? $order['pickupAddress']['phone'] ?? '' }}<br>
                </td>
            </tr>
        </table>

        <table id="items">
            <thead>
                <tr>
                    <th>分類</th>
                    <th>描述</th>
                    <th>數量</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order['items'] as $item)
                    <tr>
                        {{-- <td>{{ implode(',', $item['properties']['categories']) }}</td> --}}
                        <td>{{ $item['properties']['attribute_name'] ?? '' }}</td>
                        <td>
                            {{ $item['product_name'] }}
                            <br>
                            @isset($item['period'])
                                <small>{{ $item['period'] }}</small>
                            @endisset
                        </td>
                        <td>{{ $item['quantity'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- 簽章區 -->
        <div style="margin-top: 50px;">
            <table style="width: 100%; border: none;">
                <!-- 第一行：備註 -->
                <tr>
                    <td style="width: 100%; border: none;" colspan="2">
                        <p>備　　註：</p>
                        <p>{{ $order['note'] ?? '　' }}</p>
                        <p>＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿</p>
                    </td>
                </tr>

                <!-- 第二行：銷售人員 + 組裝人員 -->
                <tr>
                    <td style="width: 50%; border: none;">
                        <p>銷售人員：　{{ $order['attributionUser']['name'] ?? '　' }}</p>
                        <p>＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿</p>
                    </td>
                    <td style="width: 50%; text-align: right; border: none;">
                        <p>組裝人員：</p>
                        <p>＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿</p>
                    </td>
                </tr>
            </table>
        </div>

    </main>

</body>

</html>
