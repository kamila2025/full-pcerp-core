<?php

namespace App\Http\Controllers;

use App\Enums\PermissionNameEnum;
use App\Enums\ProductVariant\ProductVariantStatusEnum;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductExport;
use App\Imports\ProductImport;
use App\Models\Category;
use App\Repositories\ProductRepository;
use App\Services\ProductService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Prettus\Validator\Contracts\ValidatorInterface;
use Prettus\Validator\LaravelValidator;
use Spatie\Tags\Tag;

class AdminProductController extends Controller
{
    public function __construct(protected ProductService $productService, protected ProductRepository $productRepository, protected LaravelValidator $validator)
    {
        $this->validator
            ->setRules([
                ValidatorInterface::RULE_CREATE => [
                    'name'                                  => 'required|string|max:255',
                    'description'                           => 'nullable|string',
                    'inventory_management'                  => 'required|string|in:store,none',
                    'status'                                => 'required|string|in:published,unpublished',
                    // 分類
                    'categories'                            => 'nullable|array',
                    // 品牌
                    'brands'                                => 'nullable|array',
                    // 多規格
                    'variants'                              => 'required|array',
                    'variants.*.price'                      => 'required|numeric',
                    'variants.*.compare_at_price'           => 'nullable|numeric',
                    'variants.*.cost_price'                 => 'nullable|numeric',
                    'variants.*.status'                     => 'nullable|string|in:' . implode(',', array_column(ProductVariantStatusEnum::cases(), 'value')),
                    'variants.*.values'                     => 'nullable|array',
                    'variants.*.values.*.name'              => 'nullable|distinct|string|max:255',
                    // 庫存管理
                    'variants.*.inventories'                => 'required_if:inventory_management,store|array',
                    'variants.*.inventories.*.location_id'  => 'required|integer',
                    'variants.*.inventories.*.quantity'     => 'required|integer',
                    // 規格表
                    'types'                                 => 'nullable|array',
                    'types.*.name'                          => 'required|distinct|string|max:255',
                    'types.*.values'                        => 'required|array',
                    'types.*.values.*.name'                 => 'required|distinct|string|max:255',
                ],
                ValidatorInterface::RULE_UPDATE => [
                    'name'                                  => 'required|string|max:255',
                    'description'                           => 'nullable|string',
                    'inventory_management'                  => 'required|string|in:store,none',
                    'status'                                => 'required|string|in:published,unpublished',
                    // 分類
                    'categories'                            => 'nullable|array',
                    // 品牌
                    'brands'                                => 'nullable|array',
                    // 多規格
                    'variants'                              => 'required|array',
                    'variants.*.id'                         => 'nullable|integer|exists:variants,id',
                    'variants.*.price'                      => 'required|numeric',
                    'variants.*.compare_at_price'           => 'nullable|numeric',
                    'variants.*.cost_price'                 => 'nullable|numeric',
                    'variants.*.status'                     => 'nullable|string|in:' . implode(',', array_column(ProductVariantStatusEnum::cases(), 'value')),
                    'variants.*.values'                     => 'nullable|array',
                    'variants.*.values.*.name'              => 'nullable|distinct|string|max:255',
                    // 庫存管理
                    'variants.*.inventories'                => 'required_if:inventory_management,store|array',
                    'variants.*.inventories.*.location_id'  => 'required|integer|exists:locations,id',
                    'variants.*.inventories.*.quantity'     => 'required|integer',
                    // 規格表
                    'types'                                 => 'nullable|array',
                    'types.*.name'                          => 'required|distinct|string|max:255',
                    'types.*.values'                        => 'required|array',
                    'types.*.values.*.name'                 => 'required|distinct|string|max:255',
                ],
            ])
            ->setMessages([
                //
            ])
            ->setAttributes([
                //
            ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize(PermissionNameEnum::所有商品);

        $attributes = $request->validate([
            'page'      => 'nullable|integer',
            'per_page'  => 'nullable|max:100|integer|multiple_of:5',
        ]);

        return Inertia::render('Product/Index', [
            'products' => $this->productRepository->getProducts($attributes),
            'categories' => Category::query()->pluck('name', 'id'),
            'brands' => Tag::query()->where('type', 'brands')->pluck('name', 'id'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize(PermissionNameEnum::所有商品);

        return Inertia::render('Product/CreateOrEdit', [
            'locations' => Location::query()
                ->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize(PermissionNameEnum::所有商品);

        $attributes = $request->validate($this->validator->getRules(ValidatorInterface::RULE_CREATE), $this->validator->getMessages(), $this->validator->getAttributes());

        DB::transaction(function () use ($attributes) {
            $product = Product::create([
                'name'                  => $attributes['name'],
                'description'           => $attributes['description'] ?? null,
                'inventory_management'  => $attributes['inventory_management'],
                'status'                => $attributes['status'],
                'position'              => Product::max('position') + 1,
            ]);

            if (isset($attributes['categories'])) {
                $categories = array_map(fn ($category) => Category::updateOrCreate(['name' => $category]), $attributes['categories']);

                $product->categories()->sync($categories);
            }

            if (isset($attributes['brands'])) {
                $brands = array_map(fn ($brand) => Tag::findOrCreate($brand, 'brands'), $attributes['brands']);

                $product->brands()->sync($brands);
            }

            if (isset($attributes['types'])) $this->productService->updateTypes($attributes['types'], $product);

            $this->productService->updateVariants($attributes['variants'], $product);
        });

        return redirect()->route('products.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        Gate::authorize(PermissionNameEnum::所有商品);

        return Inertia::render('Product/CreateOrEdit', [
            'product' => Product::query()
                ->with('categories', 'variants.inventories', 'variants.values', 'types.values')
                ->with(['brands' => fn ($query) => match (config('database.default')) {
                    'mysql' => $query->select('*', DB::raw("JSON_UNQUOTE(JSON_EXTRACT(name, '$." . app()->getLocale() . "')) as name_display")),
                    'sqlite' => $query->select('*', DB::raw("json_extract(name, '$." . app()->getLocale() . "') as name_display")),
                }])
                ->findOrFail($id),
            'locations' => Location::query()
                ->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::所有商品);

        $attributes = $request->validate($this->validator->setId($id)->getRules(ValidatorInterface::RULE_UPDATE), $this->validator->getMessages(), $this->validator->getAttributes());

        DB::transaction(function () use ($id, $attributes) {
            $product = Product::findOrFail($id);

            $product->update([
                'name'                  => $attributes['name'],
                'description'           => $attributes['description'] ?? null,
                'inventory_management'  => $attributes['inventory_management'],
                'status'                => $attributes['status'],
            ]);

            if (isset($attributes['categories'])) {
                $categories = array_map(fn ($category) => Category::updateOrCreate(['name' => $category]), $attributes['categories']);

                $product->categories()->sync($categories);
            }

            if (isset($attributes['brands'])) {
                $brands = array_map(fn ($brand) => Tag::findOrCreate($brand, 'brands'), $attributes['brands']);

                $product->brands()->sync($brands);
            }

            if (isset($attributes['types'])) $this->productService->updateTypes($attributes['types'], $product);

            $this->productService->updateVariants($attributes['variants'], $product);
        });

        return redirect()->route('products.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize(PermissionNameEnum::所有商品);

        DB::transaction(function () use ($id) {
            Product::findOrFail($id)->delete();
        });

        return redirect()->route('products.index');
    }

    public function positions(Request $request)
    {
        Gate::authorize(PermissionNameEnum::所有商品);

        $attributes = $request->validate([
            'positions'                 => 'required|array',
            'positions.*.id'            => 'required|integer',
            'positions.*.from_position' => 'required|integer',
            'positions.*.to_position'   => 'required|integer',
        ]);

        DB::transaction(function () use ($attributes) {
            foreach ($attributes['positions'] as $position) {
                Product::findOrFail($position['id'])->update([
                    'position' => $position['to_position'],
                ]);
            }
        });

        return redirect()->route('products.index');
    }

    public function export(Request $request)
    {
        Gate::authorize(PermissionNameEnum::所有商品);

        $time = now()->format('Ymd_His');

        return Excel::download(new ProductExport, "產品_{$time}.xlsx");
    }

    public function import(Request $request)
    {
        Gate::authorize(PermissionNameEnum::所有商品);

        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        try {
            Excel::import(new ProductImport, $request->file('file'));
        } catch (\Throwable $th) {
            throw ValidationException::withMessages([
                'file' => $th->getMessage(),
            ]);
        }

        return back()->with('success', '產品資料已匯入');
    }
}
