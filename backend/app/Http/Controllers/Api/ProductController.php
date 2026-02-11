<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductListResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = Product::query()
            ->where('products.is_active', true)
            ->with([
                'category:id,name,slug',
                'images:id,product_id,path,sort_order',
            ]);

        // Search
        if ($search = trim((string) $request->query('q', ''))) {
            $q->where(function ($w) use ($search) {
                $w->where('products.name', 'like', "%{$search}%")
                  ->orWhere('products.short_description', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($categoryId = $request->integer('category_id')) {
            $q->where('products.category_id', $categoryId);
        }

        // Featured
        if ((int) $request->query('featured', 0) === 1) {
            $q->where('products.is_featured', true);
        }

        // Price filter is in DENARS in request, but DB is cents
        $minDen = $request->query('min_price');
        $maxDen = $request->query('max_price');
        $minCents = is_numeric($minDen) ? (int) $minDen * 100 : null;
        $maxCents = is_numeric($maxDen) ? (int) $maxDen * 100 : null;

        // We'll compute min variant price per product, and stock flag.
        $q->select('products.*')
          ->selectSub(function ($sub) {
              $sub->from('product_variants')
                  ->whereColumn('product_variants.product_id', 'products.id')
                  ->where('product_variants.is_active', true)
                  ->selectRaw('MIN(COALESCE(product_variants.sale_price_cents, product_variants.price_cents))');
          }, 'min_price_cents')
          ->selectSub(function ($sub) {
              // has_stock = exists active variant that is either not tracked or has qty > 0
              $sub->from('product_variants')
                  ->whereColumn('product_variants.product_id', 'products.id')
                  ->where('product_variants.is_active', true)
                  ->where(function ($w) {
                      $w->where('product_variants.track_stock', false)
                        ->orWhere('product_variants.stock_qty', '>', 0);
                  })
                  ->selectRaw('COUNT(*) > 0');
          }, 'has_stock');

        if ($minCents !== null) {
            $q->having('min_price_cents', '>=', $minCents);
        }
        if ($maxCents !== null) {
            $q->having('min_price_cents', '<=', $maxCents);
        }

        // In stock filter
        if ((int) $request->query('in_stock', 0) === 1) {
            $q->having('has_stock', '=', 1);
        }

        // Sorting
        $sort = (string) $request->query('sort', 'newest');
        if ($sort === 'price_asc') {
            $q->orderBy('min_price_cents', 'asc')->orderBy('products.id', 'desc');
        } elseif ($sort === 'price_desc') {
            $q->orderBy('min_price_cents', 'desc')->orderBy('products.id', 'desc');
        } else {
            $q->orderBy('products.id', 'desc');
        }

        $perPage = min(max((int) $request->query('per_page', 12), 1), 50);

        return ProductListResource::collection(
            $q->paginate($perPage)->withQueryString()
        );
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'category:id,name,slug',
            'images:id,product_id,path,alt,sort_order',
            'variants' => function ($q) {
                $q->where('is_active', true)->orderBy('id');
            },
        ]);

        return new ProductDetailResource($product);
    }
}
