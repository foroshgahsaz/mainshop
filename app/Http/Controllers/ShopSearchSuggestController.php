<?php

namespace App\Http\Controllers;

use App\Services\Cache\ShopCacheService;
use App\Support\ShopFormatter;
use App\Support\ShopLabels;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopSearchSuggestController extends Controller
{
    public function __invoke(Request $request, ShopCacheService $cache): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 3) {
            return response()->json([
                'query' => $query,
                'total' => 0,
                'items' => [],
            ]);
        }

        $result = $cache->searchSuggestions($query, 5);

        $items = collect($result['items'])->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'url' => route('products.show', $product),
                'image' => ShopFormatter::productImage($product),
                'price' => ShopLabels::formatMoney($product->effective_price),
            ];
        })->values();

        return response()->json([
            'query' => $query,
            'total' => $result['total'],
            'items' => $items,
            'all_results_url' => route('products.index', ['search' => $query]),
        ]);
    }
}
