<?php

namespace App\Exports;

use App\Repositories\PurchaseOrderRepository;
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

class PurchaseOrderExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithTitle, WithColumnFormatting, WithCustomValueBinder, WithEvents
{
    protected $purchaseOrderRepository;

    public function __construct()
    {
        $this->purchaseOrderRepository = app(PurchaseOrderRepository::class);
    }

    public function collection()
    {
        $purchaseOrders = $this->purchaseOrderRepository
            ->with([
                'items.variant.product',
                'items.order.customer',
                'user',
                'location',
                'executorUser'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $collection = new Collection();

        foreach ($purchaseOrders as $purchaseOrder) {
            // 如果進貨單沒有商品項目，至少輸出進貨單基本資訊
            if ($purchaseOrder->items->isEmpty()) {
                $collection->push($this->mapPurchaseOrderRow($purchaseOrder, null, true));
            } else {
                // 為每個商品項目創建一行，第一個項目顯示進貨單號碼
                $isFirstItem = true;
                foreach ($purchaseOrder->items as $item) {
                    $collection->push($this->mapPurchaseOrderRow($purchaseOrder, $item, $isFirstItem));
                    $isFirstItem = false;
                }
            }
        }

        return $collection;
    }

    public function bindValue($cell, $value)
    {
        // 進貨單編號欄位強制使用文字格式
        if ($cell->getColumn() === 'A') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        // 關聯訂單號碼欄位強制使用文字格式 (第23欄，即W欄)
        if ($cell->getColumn() === 'W') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    protected function mapPurchaseOrderRow($purchaseOrder, $item = null, $showOrderNumber = true)
    {
        // 基本進貨單資訊（只在第一筆顯示）
        $orderNumber = $showOrderNumber ? $purchaseOrder->order_number : '';
        $customNumber = $showOrderNumber ? ($purchaseOrder->custom_number ?? '') : '';
        $orderDate = $showOrderNumber ? $purchaseOrder->created_at->format('Y-m-d H:i:s') : '';
        $type = $showOrderNumber ? ($purchaseOrder->type?->name ?? '') : '';
        $status = $showOrderNumber ? ($purchaseOrder->status?->name ?? '') : '';
        $arrivalStatus = $showOrderNumber ? ($purchaseOrder->arrival_status?->name ?? '') : '';

        // 人員資訊（只在第一筆顯示）
        $issuerUser = $showOrderNumber ? ($purchaseOrder->user?->name ?? '') : '';
        $executorUser = $showOrderNumber ? ($purchaseOrder->executorUser?->name ?? '') : '';
        $location = $showOrderNumber ? ($purchaseOrder->location?->name ?? '') : '';

        // 時間資訊（只在第一筆顯示）
        $scheduledTime = $showOrderNumber ? ($purchaseOrder->scheduled_time ? $purchaseOrder->scheduled_time : '') : '';
        $actualTime = $showOrderNumber ? ($purchaseOrder->actual_time ? $purchaseOrder->actual_time : '') : '';

        // 金額資訊（只在第一筆顯示）
        $otherFee = $showOrderNumber ? ((float) $purchaseOrder->other_fee ?: 0) : '';
        $totalAmount = $showOrderNumber ? ((float) $purchaseOrder->total_amount ?: 0) : '';

        // 備註（只在第一筆顯示）
        $remark = $showOrderNumber ? ($purchaseOrder->remark ?? '') : '';

        // 商品資訊（如果有商品項目）
        $productName = '';
        $variantName = '';
        $productSku = '';
        $productBarcode = '';
        $itemPrice = 0;
        $itemCostPrice = 0;
        $itemQuantity = 0;
        $itemTotalCost = 0;

        // 關聯單據資訊
        $relatedOrderNumber = '';
        $relatedOrderDate = '';
        $relatedOrderCustomer = '';

        if ($item) {
            $productName = $item->variant?->product?->name ?? '';
            $variantName = $item->variant?->name ?? '';
            $productSku = $item->variant?->sku ?? '';
            $productBarcode = $item->variant?->barcode ?? '';
            $itemPrice = (float) $item->price ?: 0;
            $itemCostPrice = (float) $item->cost_price ?: 0;
            $itemQuantity = (int) $item->quantity ?: 0;
            $itemTotalCost = (float) $item->total_cost ?: 0;

            // 關聯的訂單資訊
            if ($item->order && $item->order->order) {
                $relatedOrderNumber = $item->order->order->order_number ?? '';
                $relatedOrderDate = $item->order->order->created_at ? $item->order->order->created_at->format('Y-m-d H:i:s') : '';
                $relatedOrderCustomer = $item->order->order->customer?->name ?? '';
            }
        }

        return [
            $orderNumber,           // 進貨單號碼
            $customNumber,          // 自訂編號
            $orderDate,             // 建立日期
            $type,                  // 種類
            $status,                // 貨單狀態
            $arrivalStatus,         // 到貨狀態
            $issuerUser,            // 發起人
            $executorUser,          // 執行者
            $location,              // 進貨地點
            $scheduledTime,         // 預定到貨日期
            $actualTime,            // 實際到貨日期
            $otherFee,              // 其他費用
            $totalAmount,           // 預期成本
            $remark,                // 備註
            $productName,           // 商品名稱
            $variantName,           // 規格名稱
            $productSku,            // 商品編號
            $productBarcode,        // 商品條碼
            $itemPrice,             // 單價
            $itemCostPrice,         // 成本價
            $itemQuantity,          // 數量
            $itemTotalCost,         // 小計
            $relatedOrderNumber,    // 關聯訂單號碼
            $relatedOrderDate,      // 關聯訂單日期
            $relatedOrderCustomer,  // 關聯訂單客戶
        ];
    }

    public function headings(): array
    {
        return [
            '進貨單號碼',
            '自訂編號',
            '建立日期',
            '種類',
            '貨單狀態',
            '到貨狀態',
            '發起人',
            '執行者',
            '進貨地點',
            '預定到貨日期',
            '實際到貨日期',
            '其他費用',
            '預期成本',
            '備註',
            '商品名稱',
            '規格名稱',
            '商品編號',
            '商品條碼',
            '單價',
            '成本價',
            '數量',
            '小計',
            '關聯訂單號碼',
            '關聯訂單日期',
            '關聯訂單客戶',
        ];
    }

    public function columnFormats(): array
    {
        $customFormat = '#,##0';  // 加上千分位分隔符號

        return [
            'L' => $customFormat,  // 其他費用
            'M' => $customFormat,  // 預期成本
            'S' => $customFormat,  // 單價
            'T' => $customFormat,  // 成本價
            'U' => '#,##0',        // 數量
            'V' => $customFormat,  // 小計
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // 設定標題列樣式
                $sheet->getStyle('A1:Y1')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'CCCCCC'],
                    ],
                ]);

                // 自動調整欄寬
                foreach (range('A', 'Y') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                // 設定邊框
                $sheet->getStyle('A1:Y' . $sheet->getHighestRow())->applyFromArray([
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
        return '進貨單明細';
    }
}
