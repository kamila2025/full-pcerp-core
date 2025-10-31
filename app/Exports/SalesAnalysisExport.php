<?php

namespace App\Exports;

use App\Enums\Transaction\TransactionStatusEnum;
use App\Enums\Order\OrderFulfillmentStatusEnum;
use App\Enums\Order\OrderFinancialStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SalesAnalysisExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithTitle, WithColumnFormatting, WithCustomValueBinder, WithEvents
{
    protected $startDate;
    protected $endDate;
    protected $currentSalesperson = null;
    protected $subtotalRows = [];
    protected $rowCount = 1; // 從標題列開始計數
    protected $lastSalespersonId = null;
    protected $currentSalespersonStartRow = 2;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function query()
    {
        return Order::query()
            ->with(['customer', 'attributionUser', 'items', 'transactions'])
            ->when($this->startDate && $this->endDate, function ($query) {
                $query->whereTimestampBetween('created_at', $this->startDate->timestamp, $this->endDate->timestamp);
            })
            ->whereIn('orders.status', [OrderStatusEnum::開啟, OrderStatusEnum::封存])
            ->orderBy('attribution_user_id')
            ->orderBy('created_at', 'desc');
    }

    public function bindValue($cell, $value)
    {
        // 如果是訂單編號欄位（B 列），強制使用文字格式
        if ($cell->getColumn() === 'B') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function map($order): array
    {
        $this->rowCount++;

        // 檢查是否需要插入小計列
        if ($this->currentSalesperson !== null && $this->currentSalesperson !== $order->attribution_user_id) {
            // 只在销售人员改变时添加小计行，并记录新销售人员的开始行
            $this->subtotalRows[] = [
                'row' => $this->rowCount - 1,
                'startRow' => $this->currentSalespersonStartRow,
                'salespersonId' => $this->lastSalespersonId
            ];
            $this->currentSalespersonStartRow = $this->rowCount;
        }

        $this->lastSalespersonId = $order->attribution_user_id;
        $this->currentSalesperson = $order->attribution_user_id;

        // 確保所有數值都是數字，避免 null 或空值
        $amount = (float) $order->amount ?: 0;

        // 商品成本
        $totalCostPrice = $order->items->sum(function ($item) {
            return ((float) $item->cost_price ?: 0) * ((float) $item->quantity ?: 0);
        });

        // 交易手續費
        $totalFee = (float) $order->transactions->sum('fee') ?: 0;

        // 已收款金額
        $totalPaid = (float) $order->transactions->where('status', TransactionStatusEnum::已付款)->sum('amount') ?: 0;

        // 物流費
        $shippingFee = (float) $order->total_shipping ?: 0;

        // 營業稅 (5%)
        $salesTax = $order->tax_included ? ($amount / 1.05 * 0.05) : ((float) $order->total_tax ?: 0);

        // 總成本 = 商品成本 + 交易手續費 + 物流費 + 營業稅
        $totalCost = $totalCostPrice + $totalFee + $shippingFee + $salesTax;

        // 毛利潤 = 總銷售額 - 總成本
        $grossProfit = $amount - $totalCost;

        return [
            $order->attributionUser?->name ?? '',
            $order->order_number,  // 訂單編號會由 bindValue 方法處理格式
            $order->created_at->format('Y-m-d H:i:s'),
            $order->customer?->name ?? '',
            $amount,          // 總銷售額
            $totalPaid,      // 總收款額
            $totalCostPrice, // 商品成本
            $totalFee,       // 交易手續費
            $shippingFee,    // 物流費
            $salesTax,       // 營業稅
            $grossProfit,    // 毛利潤
            $order->fulfillment_status?->name ?? '',  // 使用 Enum 的 name
            $order->financial_status?->name ?? '',    // 使用 Enum 的 name
        ];
    }

    public function headings(): array
    {
        return [
            '銷售員',
            '訂單編號',
            '訂單日期',
            '客戶名稱',
            '總銷售額',
            '總收款額',
            '商品成本',
            '交易手續費',
            '物流費',
            '營業稅',
            '毛利潤',
            '出貨狀態',
            '付款狀態',
        ];
    }

    public function columnFormats(): array
    {
        $customFormat = '#,##0';  // 加上千分位分隔符號

        return [
            'E' => $customFormat,
            'F' => $customFormat,
            'G' => $customFormat,
            'H' => $customFormat,
            'I' => $customFormat,
            'J' => $customFormat,
            'K' => $customFormat,
            'L' => $customFormat,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $lastRow = $sheet->getHighestRow();

                // 添加最后一个销售员的小计
                if ($this->lastSalespersonId !== null) {
                    $this->subtotalRows[] = [
                        'row' => $lastRow,
                        'startRow' => $this->currentSalespersonStartRow,
                        'salespersonId' => $this->lastSalespersonId
                    ];
                }

                // 設定標題列樣式
                $sheet->getStyle('A1:M1')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'CCCCCC'],
                    ],
                ]);

                // 處理每個小計列
                $rowOffset = 0;
                foreach ($this->subtotalRows as $index => $subtotalInfo) {
                    $row = $subtotalInfo['row'] + $rowOffset;
                    $startRow = $subtotalInfo['startRow'];

                    // 插入小計列
                    $sheet->insertNewRowBefore($row + 1);
                    $rowOffset++;
                    $lastRow++;

                    // 設定小計列樣式
                    $subtotalRow = $row + 1;
                    $sheet->getStyle("A{$subtotalRow}:M{$subtotalRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F0F0F0'],
                        ],
                    ]);

                    // 設定小計公式，只計算當前銷售員的數據
                    $salespersonName = $sheet->getCell("A{$row}")->getValue();

                    // 在隱藏的列計算訂單數量
                    $countCell = "Y" . $subtotalRow;  // 計數儲存格
                    $formattedCountCell = "Z" . $subtotalRow;  // 格式化後的儲存格

                    // 計算訂單數量
                    $sheet->setCellValue($countCell, "=COUNTIFS(A{$startRow}:A{$row}, \"{$salespersonName}\")");

                    // 使用 TEXT 函數格式化數字
                    $sheet->setCellValue($formattedCountCell, "=TEXT(" . $countCell . ", \"#,##0\")");

                    // 設定小計標題（包含訂單數量）
                    $sheet->setCellValue(
                        "A{$subtotalRow}",
                        "=CONCATENATE(\"" . $salespersonName . " 小計 (共 \", " . $formattedCountCell . ", \" 筆訂單)\")"
                    );

                    // 使用 SUMIF 來只計算特定銷售員的數據
                    $sheet->setCellValue("E{$subtotalRow}", "=SUMIFS(E{$startRow}:E{$row}, A{$startRow}:A{$row}, \"{$salespersonName}\")");
                    $sheet->setCellValue("F{$subtotalRow}", "=SUMIFS(F{$startRow}:F{$row}, A{$startRow}:A{$row}, \"{$salespersonName}\")");
                    $sheet->setCellValue("G{$subtotalRow}", "=SUMIFS(G{$startRow}:G{$row}, A{$startRow}:A{$row}, \"{$salespersonName}\")");
                    $sheet->setCellValue("H{$subtotalRow}", "=SUMIFS(H{$startRow}:H{$row}, A{$startRow}:A{$row}, \"{$salespersonName}\")");
                    $sheet->setCellValue("I{$subtotalRow}", "=SUMIFS(I{$startRow}:I{$row}, A{$startRow}:A{$row}, \"{$salespersonName}\")");
                    $sheet->setCellValue("J{$subtotalRow}", "=SUMIFS(J{$startRow}:J{$row}, A{$startRow}:A{$row}, \"{$salespersonName}\")");
                    $sheet->setCellValue("K{$subtotalRow}", "=SUMIFS(K{$startRow}:K{$row}, A{$startRow}:A{$row}, \"{$salespersonName}\")");

                    // 為小計行設定數字格式
                    $sheet->getStyle("E{$subtotalRow}:K{$subtotalRow}")->getNumberFormat()->setFormatCode('#,##0');
                }

                // 添加總計列
                $totalRow = $lastRow + 1;
                $sheet->getStyle("A{$totalRow}:M{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E6E6E6'],
                    ],
                ]);

                // 計算總訂單數
                $totalCountCell = "Y" . $totalRow;  // 計數儲存格
                $formattedTotalCountCell = "Z" . $totalRow;  // 格式化後的儲存格

                // 計算總訂單數
                $sheet->setCellValue($totalCountCell, "=COUNTIFS(A2:A{$lastRow}, \"<>*小計*\")");

                // 格式化總訂單數
                $sheet->setCellValue($formattedTotalCountCell, "=TEXT(" . $totalCountCell . ", \"#,##0\")");

                // 設定總計標題（包含訂單總數）
                $sheet->setCellValue(
                    "A{$totalRow}",
                    "=CONCATENATE(\"總計 (共 \", " . $formattedTotalCountCell . ", \" 筆訂單)\")"
                );

                // 修改總計公式，排除小計行
                $sheet->setCellValue("E{$totalRow}", "=SUMIFS(E2:E{$lastRow}, A2:A{$lastRow}, \"<>*小計*\")");
                $sheet->setCellValue("F{$totalRow}", "=SUMIFS(F2:F{$lastRow}, A2:A{$lastRow}, \"<>*小計*\")");
                $sheet->setCellValue("G{$totalRow}", "=SUMIFS(G2:G{$lastRow}, A2:A{$lastRow}, \"<>*小計*\")");
                $sheet->setCellValue("H{$totalRow}", "=SUMIFS(H2:H{$lastRow}, A2:A{$lastRow}, \"<>*小計*\")");
                $sheet->setCellValue("I{$totalRow}", "=SUMIFS(I2:I{$lastRow}, A2:A{$lastRow}, \"<>*小計*\")");
                $sheet->setCellValue("J{$totalRow}", "=SUMIFS(J2:J{$lastRow}, A2:A{$lastRow}, \"<>*小計*\")");
                $sheet->setCellValue("K{$totalRow}", "=SUMIFS(K2:K{$lastRow}, A2:A{$lastRow}, \"<>*小計*\")");

                // 為總計行設定數字格式
                $sheet->getStyle("E{$totalRow}:K{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');

                // 隱藏用於計算的臨時列
                $sheet->getColumnDimension('Y')->setVisible(false);
                $sheet->getColumnDimension('Z')->setVisible(false);

                // 自動調整欄寬
                foreach (range('A', 'M') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
    }

    public function title(): string
    {
        return '銷售員分析';
    }
}
