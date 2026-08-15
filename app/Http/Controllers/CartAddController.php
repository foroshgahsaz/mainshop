<?php

namespace App\Http\Controllers;

use App\Services\Cart\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class CartAddController extends Controller
{
    public function __invoke(Request $request, CartService $cart): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        try {
            $cart->add(
                (int) $validated['product_id'],
                (int) ($validated['quantity'] ?? 1),
                $validated['product_variant_id'] ?? null
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'به سبد خرید اضافه شد.');
    }
}
