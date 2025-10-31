<?php

namespace App\Exports;

use App\Enums\Product\ProductInventoryManagementEnum;
use App\Models\Location;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $locations;

    protected $headers = [
        'Handle' => '商品網址*',
        'Title' => '商品標題*',
        'Published' => '已發布*',
        'Track Inventory' => '追蹤庫存*',
        'Collection' => '商品分類',
        'Brand' => '品牌',
        'Option1 Name' => '選項1名稱',
        'Option1 Value' => '選項1數值',
        'Option2 Name' => '選項2名稱',
        'Option2 Value' => '選項2數值',
        'Option3 Name' => '選項3名稱',
        'Option3 Value' => '選項3數值',
        'SKU' => '商品編號',
        'Barcode' => '商品條碼',
        'Price' => '售價*',
        'Compare At Price' => '比較價格',
        'Cost Price' => '成本價格',
    ];

    public function __construct()
    {
        $this->locations = Location::all();
    }

    public function collection()
    {
        return Product::query()
            ->with([
                'variants.inventories',
                'categories',
                'brands',
                'variants.values.type',
            ])
            ->orderBy('position', 'desc')
            ->get();
    }

    public function headings(): array
    {
        $locationCodes = $this->locations->map(fn ($location) => $location->code . ' Inventory')->toArray();

        $locationNames = $this->locations->map(fn ($location) => $location->name . ' 庫存')->toArray();

        return [
            array_merge(array_keys($this->headers), $locationCodes),
            array_merge(array_values($this->headers), $locationNames),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // 設定標題樣式
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '2')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E2EFDA',
                ],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        // 找出所有必填欄位（帶有*的欄位）
        $requiredColumns = [];
        $column = 'A';
        foreach ($this->headers as $header) {
            if (str_ends_with($header, '*')) {
                $requiredColumns[] = $column;
            }
            $column++;
        }

        // 設定必填欄位的*為紅色
        foreach ($requiredColumns as $column) {
            $sheet->getStyle($column . '2')->getFont()->getColor()->setRGB('FF0000');
        }

        // 設定所有資料儲存格的對齊方式
        $sheet->getStyle('A3:' . $sheet->getHighestColumn() . $sheet->getHighestRow())
            ->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // 凍結窗格
        $sheet->freezePane('A3');

        // 設定標題列高度
        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->getRowDimension(2)->setRowHeight(25);
    }

    public function map($product): array
    {
        $rows = [];

        // 如果產品沒有變體，創建一個預設變體行
        if ($product->variants->isEmpty()) {
            $rows[] = $this->createRow($product, null);
            return $rows;
        }

        // 對每個變體創建一行
        foreach ($product->variants as $variant) {
            $rows[] = $this->createRow($product, $variant);
        }

        return $rows;
    }

    protected function createRow($product, $variant = null): array
    {
        // 基本資料
        $row = [
            $product->handle,
            $product->name,
            $product->status === 'published' ? 'Yes' : 'No',
            $product->inventory_management === ProductInventoryManagementEnum::庫存管理 ? 'Yes' : 'No',
            $product->categories->pluck('name')->first() ?? '',
            $product->brands->pluck('name')->first() ?? '',
        ];

        // 規格選項
        if ($variant && $variant->values) {
            // 按照規格類型的 position 排序
            $valuesByType = $variant->values->sortBy('type.position');

            // 最多處理三個選項
            for ($i = 1; $i <= 3; $i++) {
                $value = $valuesByType->get($i - 1);
                $row[] = $value ? $value->type->name : ''; // Option Name
                $row[] = $value ? $value->name : ''; // Option Value
            }
        } else {
            // 如果沒有選項，填充空值
            for ($i = 1; $i <= 3; $i++) {
                $row[] = ''; // Option Name
                $row[] = ''; // Option Value
            }
        }

        // 變體資訊
        $row[] = $variant ? $variant->sku : '';
        $row[] = $variant ? $variant->barcode : '';
        $row[] = $variant ? strval($variant->price) : '0';
        $row[] = $variant && $variant->compare_at_price ? strval($variant->compare_at_price) : '';
        $row[] = $variant && $variant->cost_price ? strval($variant->cost_price) : '';

        foreach ($this->locations as $location) {
            $row[] = $variant->inventories->where('location_id', $location->id)->value('quantity') ?? null;
        }

        return $row;
    }
}
