<?php

namespace App\Exports;

use App\Enums\Transaction\TransactionStatusEnum;
use App\Repositories\OrderRepository;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Collection;

class OrderExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithTitle, WithColumnFormatting, WithCustomValueBinder, WithEvents
{
    protected $orderRepository;

    public function __construct()
    {
        $this->orderRepository = app(OrderRepository::class);
    }

    public function collection()
    {
        $query = $this->orderRepository->with([
            'customer',
            'attributionUser',
            'items' => function ($query) {
                $query->leftJoin('form_template_items', 'order_items.template_item_id', '=', 'form_template_items.id')
                      ->orderBy('form_template_items.position', 'asc')
                      ->orderBy('order_items.id', 'asc')
                      ->select('order_items.*'); // 確保只選擇 order_items 的欄位
            },
            'items.product',
            'items.variant',
            'location',
            'shippingLocation',
            'transactions',
            'shippingAddress',
            'pickupAddress'
        ]);

        $orders = $query
            // ->orderBy('created_at', 'desc')
            ->get();

        $collection = new Collection();

        foreach ($orders as $order) {
            // 如果訂單沒有商品項目，至少輸出訂單基本資訊
            if ($order->items->isEmpty()) {
                $collection->push($this->mapOrderRow($order, null, true));
            } else {
                // 為每個商品項目創建一行
                $isFirstItem = true;
                foreach ($order->items as $item) {
                    $collection->push($this->mapOrderRow($order, $item, $isFirstItem));
                    $isFirstItem = false;
                }
            }
        }

        return $collection;
    }

    public function bindValue($cell, $value)
    {
        // 訂單編號欄位強制使用文字格式
        if ($cell->getColumn() === 'A') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    protected function mapOrderRow($order, $item = null, $isFirstItem = true)
    {
        // 基本訂單資訊
        $orderNumber = $isFirstItem ? $order->order_number : '';
        $orderDate = $isFirstItem ? $order->created_at->format('Y-m-d H:i:s') : '';
        $customerName = $isFirstItem ? ($order->customer?->name ?? '') : '';
        $customerEmail = $isFirstItem ? ($order->customer?->email ?? '') : '';
        $customerPhone = $isFirstItem ? ($order->customer?->phone ?? '') : '';
        $salesperson = $isFirstItem ? ($order->attributionUser?->name ?? '') : '';
        $location = $isFirstItem ? ($order->location?->name ?? '') : '';
        $shippingLocation = $isFirstItem ? ($order->shippingLocation?->name ?? '') : '';

        // 付款和出貨狀態
        $financialStatus = $isFirstItem ? ($order->financial_status?->name ?? '') : '';
        $fulfillmentStatus = $isFirstItem ? ($order->fulfillment_status?->name ?? '') : '';
        $orderStatus = $isFirstItem ? ($order->status?->name ?? '') : '';

        // 配送資訊
        $deliveryType = $isFirstItem ? ($order->delivery_type?->name ?? '') : '';
        $shippingAddress = '';
        $pickupAddress = '';

        if ($isFirstItem && $order->shippingAddress) {
            $shippingAddress = implode(', ', array_filter([
                $order->shippingAddress->full_name,
                $order->shippingAddress->company,
                $order->shippingAddress->address1,
                $order->shippingAddress->phone
            ]));
        }

        if ($isFirstItem && $order->pickupAddress) {
            $pickupAddress = implode(', ', array_filter([
                $order->pickupAddress->full_name,
                $order->pickupAddress->phone
            ]));
        }

        // 金額資訊
        $subtotal = $isFirstItem ? ((float) $order->subtotal_price ?: 0) : 0;
        $totalDiscount = $isFirstItem ? ((float) $order->total_discount ?: 0) : 0;
        $totalShipping = $isFirstItem ? ((float) $order->total_shipping ?: 0) : 0;
        $totalTax = $isFirstItem ? ((float) $order->total_tax ?: 0) : 0;
        $totalAmount = $isFirstItem ? ((float) $order->amount ?: 0) : 0;

        // 已付款金額
        $paidAmount = $isFirstItem ? ((float) $order->transactions->where('status', TransactionStatusEnum::已付款)->sum('amount') ?: 0) : 0;

        // 商品資訊（如果有商品項目）
        $productName = '';
        $variantName = '';
        $productSku = '';
        $itemPrice = 0;
        $itemCostPrice = 0;
        $itemQuantity = 0;
        $itemSubtotal = 0;
        $itemDiscount = 0;
        $itemTax = 0;
        $itemTotal = 0;

        if ($item) {
            $productName = $item->product_name ?: ($item->product?->name ?? '');
            $variantName = $item->variant_name ?: ($item->variant?->name ?? '');
            $productSku = $item->product?->sku ?? '';
            $itemPrice = (float) $item->price ?: 0;
            $itemCostPrice = (float) $item->cost_price ?: 0;
            $itemQuantity = (int) $item->quantity ?: 0;
            $itemSubtotal = (float) $item->subtotal ?: 0;
            $itemDiscount = (float) $item->total_discount ?: 0;
            $itemTax = (float) $item->total_tax ?: 0;
            $itemTotal = (float) $item->total_amount ?: 0;
        }

        // 備註
        $note = $isFirstItem ? ($order->note ?? '') : '';
        $remark = $isFirstItem ? ($order->remark ?? '') : '';

        return [
            $orderNumber,           // 訂單編號
            $orderDate,             // 訂單日期
            $customerName,          // 客戶姓名
            $customerEmail,         // 客戶信箱
            $customerPhone,         // 客戶電話
            $salesperson,           // 銷售員
            $location,              // 地點
            $shippingLocation,      // 配送地點
            $financialStatus,       // 付款狀態
            $fulfillmentStatus,     // 出貨狀態
            $orderStatus,           // 訂單狀態
            $deliveryType,          // 配送方式
            $shippingAddress,       // 配送地址
            $pickupAddress,         // 自取地址
            $productName,           // 商品名稱
            $variantName,           // 規格名稱
            $productSku,            // 商品編號
            $itemPrice,             // 單價
            $itemCostPrice,         // 成本價
            $itemQuantity,          // 數量
            $itemSubtotal,          // 小計
            $itemDiscount,          // 商品折扣
            $itemTax,               // 商品稅金
            $itemTotal,             // 商品總計
            $subtotal,              // 訂單小計
            $totalDiscount,         // 訂單折扣
            $totalShipping,         // 運費
            $totalTax,              // 訂單稅金
            $totalAmount,           // 訂單總計
            $paidAmount,            // 已付款金額
            $note,                  // 商家備註
            $remark,                // 顧客備註
        ];
    }

    public function headings(): array
    {
        return [
            '訂單編號',
            '訂單日期',
            '客戶姓名',
            '客戶信箱',
            '客戶電話',
            '銷售員',
            '地點',
            '配送地點',
            '付款狀態',
            '出貨狀態',
            '訂單狀態',
            '配送方式',
            '配送地址',
            '自取地址',
            '商品名稱',
            '規格名稱',
            '商品編號',
            '單價',
            '成本價',
            '數量',
            '小計',
            '商品折扣',
            '商品稅金',
            '商品總計',
            '訂單小計',
            '訂單折扣',
            '運費',
            '訂單稅金',
            '訂單總計',
            '已付款金額',
            '商家備註',
            '顧客備註',
        ];
    }

    public function columnFormats(): array
    {
        $customFormat = '#,##0';  // 加上千分位分隔符號

        return [
            'R' => $customFormat,  // 單價
            'S' => $customFormat,  // 成本價
            'T' => '#,##0',        // 數量
            'U' => $customFormat,  // 小計
            'V' => $customFormat,  // 商品折扣
            'W' => $customFormat,  // 商品稅金
            'X' => $customFormat,  // 商品總計
            'Y' => $customFormat,  // 訂單小計
            'Z' => $customFormat,  // 訂單折扣
            'AA' => $customFormat, // 運費
            'AB' => $customFormat, // 訂單稅金
            'AC' => $customFormat, // 訂單總計
            'AD' => $customFormat, // 已付款金額
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // 設定標題列樣式
                $sheet->getStyle('A1:AE1')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'CCCCCC'],
                    ],
                ]);

                // 自動調整欄寬
                $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE'];
                // foreach (range('A', 'AE') as $column) {
                foreach ($columns as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                // 設定邊框
                $sheet->getStyle('A1:AE' . $sheet->getHighestRow())->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
            },
        ];
    }

    public function title(): string
    {
        return '訂單明細';
    }
}
