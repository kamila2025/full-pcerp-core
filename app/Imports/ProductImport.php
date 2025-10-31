<?php

namespace App\Imports;

use App\Enums\InventoryLog\InventoryLogTypeEnum;
use App\Enums\Product\ProductInventoryManagementEnum;
use App\Enums\Product\ProductStatusEnum;
use App\Enums\ProductVariant\ProductVariantStatusEnum;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Variant;
use App\Services\ProductService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Spatie\Tags\Tag;

HeadingRowFormatter::default(HeadingRowFormatter::FORMATTER_NONE);

class ProductImport extends AbstractImport implements ToCollection, WithHeadingRow, WithMultipleSheets, WithStartRow, SkipsEmptyRows
{
    protected ProductService $productService;

    protected Collection $locations;

    protected Collection $existingProducts;

    protected int $maxPosition;

    public function __construct()
    {
        $this->productService = app(ProductService::class);

        $this->locations = Location::all();

        $this->existingProducts = Product::select('id', 'handle', 'position')->get()->keyBy('handle');

        $this->maxPosition = (int) Product::max('position');
    }

    /**
     * 定義欄位映射
     *
     * @return array<string, array<string>>
     */
    protected function getFieldDefinitions(): array
    {
        return [
            'product.name' => ['商品標題*', 'Title'],
            'product.handle' => ['商品網址*', 'Handle'],
            'product.published' => ['已發布*', 'Published'],
            'product.track_inventory' => ['追蹤庫存*', 'Track Inventory'],

            'collection' => ['商品分類', 'Collection'],
            'brand' => ['商品品牌', 'Brand'],

            'variant.sku' => ['商品編號', 'SKU'],
            'variant.barcode' => ['商品條碼', 'Barcode'],
            'variant.price' => ['售價*', 'Price', '售價'],
            'variant.compare_at_price' => ['比較價格', 'Compare At Price'],
            'variant.cost_price' => ['成本價格', 'Cost Price'],

            'option.name_1' => ['選項1名稱', 'Option1 Name'],
            'option.value_1' => ['選項1數值', 'Option1 Value'],
            'option.name_2' => ['選項2名稱', 'Option2 Name'],
            'option.value_2' => ['選項2數值', 'Option2 Value'],
            'option.name_3' => ['選項3名稱', 'Option3 Name'],
            'option.value_3' => ['選項3數值', 'Option3 Value'],

        ];
    }

    public function collection(Collection $rows)
    {
        // 初始化欄位映射
        $this->resolveMappedFields(array_keys($rows->first()->toArray()));

        // 為每一行資料加上原始行號
        $rows = $rows->map(function ($row, $index) {
            $row['original_line_number'] = $index + $this->startRow();
            return $row;
        });

        // 分組處理商品資料
        DB::transaction(function () use ($rows) {
            $groupedProducts = $rows->groupBy(fn ($row) => $this->getFieldValue($row, 'product.handle'))->values()->toArray();

            $startPosition = $this->maxPosition + count($groupedProducts);

            foreach ($groupedProducts as $index => $productRows) {
                try {
                    $productData = array_merge($this->parseProductRow($productRows), [
                        'position' => $startPosition - $index,
                    ]);

                    $product = Product::updateOrCreate(['handle' => $productData['handle']], $productData);

                    if ($collection = $this->getFieldValue($productRows[0], 'collection')) {
                        $product->categories()->sync(Category::updateOrCreate(['name' => $collection]));
                    }

                    if ($brand = $this->getFieldValue($productRows[0], 'brand')) {
                        $product->brands()->sync(Tag::findOrCreate($brand, 'brands'));
                    }

                    $types = $this->parseTypeRows($productRows);

                    $this->productService->updateTypes($types, $product);

                    $variants = $this->parseVariantRows($productRows, $product->getKey());

                    $expectedVariantCount = collect($types)->map(fn ($type) => count($type['values']))->reduce(fn ($carry, $count) => $carry * $count, 1);

                    $actualVariantCount = count($variants);

                    if ($expectedVariantCount !== $actualVariantCount) {
                        throw new \Exception(sprintf(
                            "規格選項的組合數量 (%d) 與變體數量 (%d) 不符。",
                            $expectedVariantCount,
                            $actualVariantCount
                        ));
                    }

                    $this->productService->updateVariants($variants, $product, InventoryLogTypeEnum::批量商品更新);
                } catch (\Throwable $th) {
                    $errorMessage = sprintf(
                        "第 %d 行商品資料錯誤 - 商品標題：%s，商品網址：%s，錯誤訊息：%s",
                        $productRows[0]['original_line_number'],
                        $this->getFieldValue($productRows[0], 'product.name'),
                        $this->getFieldValue($productRows[0], 'product.handle'),
                        $th->getMessage()
                    );

                    throw new \Exception($errorMessage);
                }
            }

            Product::orderBy('position')->each(fn ($product, $index) => $product->updateQuietly(['position' => $index]));
        });
    }

    protected function parseProductRow(array $rows): array
    {
        $firstRow = $rows[0];

        $product = [
            'name' => $this->getFieldValue($firstRow, 'product.name'),
            'handle' => $this->getFieldValue($firstRow, 'product.handle'),
            'inventory_management' => match (strtolower($this->getFieldValue($firstRow, 'product.track_inventory'))) {
                'yes' => ProductInventoryManagementEnum::庫存管理,
                'no' => ProductInventoryManagementEnum::無庫存管理,
            },
            'status' => match (strtolower($this->getFieldValue($firstRow, 'product.published'))) {
                'yes' => ProductStatusEnum::上架,
                'no' => ProductStatusEnum::下架,
            },
        ];

        return $product;
    }

    protected function parseVariantRows(array $rows, int $productId): array
    {
        $variants = [];

        foreach ($rows as $row) {
            $variant = [
                'sku' => $this->getFieldValue($row, 'variant.sku'),
                'barcode' => $this->getFieldValue($row, 'variant.barcode'),
                'price' => (float) $this->getFieldValue($row, 'variant.price'),
                'compare_at_price' => (float) $this->getFieldValue($row, 'variant.compare_at_price'),
                'cost_price' => (float) $this->getFieldValue($row, 'variant.cost_price'),
                'status' => ProductVariantStatusEnum::啟用,
                'values' => [],
                'inventories' => [],
            ];

            for ($i = 1; $i <= 3; $i++) {
                if (!$optionName = $this->getFieldValue($row, "option.value_{$i}")) continue;

                $variant['values'][] = ['name' => $optionName];
            }

            $variant['id'] = Variant::where('product_id', $productId)
                ->whereHas('values', fn ($query) => $query->whereIn('name', array_column($variant['values'], 'name')), '=', count($variant['values']))
                ->when(isset($variant['sku']), fn ($query) => $query->orWhere('sku', $variant['sku']))
                ->value('id');

            foreach ($this->locations as $location) {
                // 尋找包含該位置名稱且結尾是 Inventory 的欄位
                $inventoryKey = collect($row)->keys()->first(fn ($columnName) => str_contains($columnName, $location->code) && str_ends_with($columnName, 'Inventory'));

                if ($inventoryKey && isset($row[$inventoryKey]) && $row[$inventoryKey] !== null) {
                    $variant['inventories'][] = [
                        'location_id' => $location->id,
                        'quantity' => (int) $row[$inventoryKey]
                    ];
                }
            }

            $variants[] = $variant;
        }

        return $variants;
    }

    protected function parseTypeRows(array $rows): array
    {
        $firstRow = $rows[0];

        $types = [];

        for ($i = 1; $i <= 3; $i++) {
            if (!$typeName = $this->getFieldValue($firstRow, "option.name_{$i}")) continue;

            // 直接收集所有非空的選項值並去重
            $uniqueValues = array_values(array_unique(
                array_filter(
                    array_map(fn ($row) => $this->getFieldValue($row, "option.value_{$i}"), $rows),
                    fn ($name) => !empty($name)
                )
            ));

            $types[] = [
                'name' => $typeName,
                'values' => array_map(fn ($name) => ['name' => $name], $uniqueValues),
            ];
        }

        return $types;
    }

    public function sheets(): array
    {
        return [0 => $this];
    }

    public function startRow(): int
    {
        return 3;
    }
}
