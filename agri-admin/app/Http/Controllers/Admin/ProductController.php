<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\CatalogItem;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // Danh mục mới
    private const CATEGORIES = [
        'seeds'      => 'Hạt giống',
        'tools'      => 'Dụng cụ',
        'protection' => 'Sản phẩm bảo vệ',
        'organic'    => 'Sản phẩm hữu cơ',
    ];

    public function index(Request $request)
    {
        $q = Product::query();

        if ($s = $request->q) {
            $q->where(function ($x) use ($s) {
                $x->where('name', 'like', "%$s%")
                  ->orWhere('sku', 'like', "%$s%");
            });
        }

        if ($cat = $request->get('category')) {
            $q->where('category', $cat);
        }

        $items = $q->orderBy('_id', 'desc')->paginate(20);

        // Phần này anh có thể giữ nguyên nếu vẫn xài Catalog VTNN
        $names  = $items->pluck('name')->filter()->unique()->values()->all();
        $stores = $items->pluck('vtnn_name')->filter()->unique()->values()->all();
        $catalogMap = [];

        if ($names && $stores) {
            $catalogs = CatalogItem::whereIn('product_name', $names)
                ->whereIn('vtnn_name', $stores)
                ->get(['product_name', 'vtnn_name', 'discount_tn', 'discount_ln']);

            foreach ($catalogs as $c) {
                $key = ($c->product_name ?? '') . '|' . ($c->vtnn_name ?? '');
                $catalogMap[$key] = [
                    'tn' => $c->discount_tn,
                    'ln' => $c->discount_ln,
                ];
            }
        }

        $categories = self::CATEGORIES;

        return view('products.index', compact('items', 'categories', 'catalogMap', 'cat'));
    }

    private function nextSku(?string $category): string
    {
        $prefixMap = [
            'seeds'      => 'SEED',
            'tools'      => 'TOOL',
            'protection' => 'PROT',
            'organic'    => 'ORG',
        ];

        $prefix = $prefixMap[$category] ?? 'SKU';

        $max = Product::when($category, fn ($q) => $q->where('category', $category))
            ->pluck('sku')
            ->map(function ($s) use ($prefix) {
                $s = (string) $s;
                if (preg_match('/^' . preg_quote($prefix, '/') . '-?(\d+)$/', $s, $m)) return (int) $m[1];
                if (preg_match('/(\d+)$/', $s, $m)) return (int) $m[1];
                return 0;
            })
            ->max() ?? 0;

        return $prefix . '-' . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }

    public function create()
    {
        $item       = new Product();
        $categories = self::CATEGORIES;
        $suppliers  = Supplier::orderBy('name')->pluck('name')->all(); // nếu không dùng có thể bỏ

        return view('products.form', compact('item', 'categories', 'suppliers'));
    }

    public function store(Request $r)
    {
        $catRule = implode(',', array_keys(self::CATEGORIES));

        $data = $r->validate([
            'name'        => 'required|string',
            'category'    => 'required|in:' . $catRule,
            'brand'       => 'nullable|string',
            'status'      => 'nullable|in:active,inactive',
            'price'       => 'nullable|numeric',
            'videoUrl'    => 'nullable|string',
            'description' => 'nullable|string',

            'images'      => 'array',
            'images.*'    => 'nullable|string',

            'variants'            => 'array',
            'variants.*.name'     => 'nullable|string',
            'variants.*.price'    => 'nullable|numeric',
            'variants.*.quantity' => 'nullable|integer',
        ]);

        // Tạo SKU mới
        $data['sku'] = $this->nextSku($r->input('category'));

        // Làm sạch mảng images
        $data['images'] = array_values(array_filter(
            $r->input('images', []),
            fn ($v) => $v !== null && $v !== ''
        ));

        // Làm sạch mảng variants
        $variants = [];
        foreach ($r->input('variants', []) as $v) {
            if (!is_array($v)) continue;

            $name = trim($v['name'] ?? '');
            if ($name === '') continue;

            $variants[] = [
                'name'     => $name,
                'price'    => isset($v['price']) ? (float) $v['price'] : 0,
                'quantity' => isset($v['quantity']) ? (int) $v['quantity'] : 0,
            ];
        }
        $data['variants'] = $variants;

        Product::create($data);

        return redirect()
            ->route('products.index')
            ->with('success', 'Đã thêm sản phẩm mới thành công !!');
    }

    public function edit(Product $product)
    {
        $item       = $product;
        $categories = self::CATEGORIES;
        $suppliers  = Supplier::orderBy('name')->pluck('name')->all();

        return view('products.form', compact('item', 'categories', 'suppliers'));
    }

    public function update(Request $r, Product $product)
    {
        $catRule = implode(',', array_keys(self::CATEGORIES));

        $data = $r->validate([
            'name'        => 'required|string',
            'category'    => 'required|in:' . $catRule,
            'brand'       => 'nullable|string',
            'status'      => 'nullable|in:active,inactive',
            'price'       => 'nullable|numeric',
            'videoUrl'    => 'nullable|string',
            'description' => 'nullable|string',

            'images'      => 'array',
            'images.*'    => 'nullable|string',

            'variants'            => 'array',
            'variants.*.name'     => 'nullable|string',
            'variants.*.price'    => 'nullable|numeric',
            'variants.*.quantity' => 'nullable|integer',
        ]);

        // images
        $data['images'] = array_values(array_filter(
            $r->input('images', []),
            fn ($v) => $v !== null && $v !== ''
        ));

        // variants
        $variants = [];
        foreach ($r->input('variants', []) as $v) {
            if (!is_array($v)) continue;

            $name = trim($v['name'] ?? '');
            if ($name === '') continue;

            $variants[] = [
                'name'     => $name,
                'price'    => isset($v['price']) ? (float) $v['price'] : 0,
                'quantity' => isset($v['quantity']) ? (int) $v['quantity'] : 0,
            ];
        }
        $data['variants'] = $variants;

        $product->update($data);

        return redirect()
            ->route('products.index')
            ->with('success', 'Đã lưu thay đổi sản phẩm thành công !!');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Đã xoá sản phẩm thành công !!');
    }
}
