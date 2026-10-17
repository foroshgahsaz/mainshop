<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Payment\DigiPayGateway;
use Tests\TestCase;

class DigiPayDeliverProductsTest extends TestCase
{
    public function test_deliver_product_titles_use_persian_product_name(): void
    {
        $order = new Order(['id' => 115]);
        $order->setRelation('items', collect([
            new OrderItem([
                'product_name' => 'کاشی ۶۰×۶۰',
                'quantity' => 2,
            ]),
            new OrderItem([
                'product_name' => 'چسب کاشی',
                'quantity' => 1,
            ]),
        ]));

        $gateway = app(DigiPayGateway::class);
        $method = new \ReflectionMethod(DigiPayGateway::class, 'deliverProductTitles');
        $method->setAccessible(true);

        $this->assertSame([
            'کاشی ۶۰×۶۰ ×2',
            'چسب کاشی',
        ], $method->invoke($gateway, $order));
    }

    public function test_deliver_product_titles_fall_back_to_product_relation(): void
    {
        $product = new Product(['name' => 'سینک ظرفشویی']);
        $item = new OrderItem(['product_name' => '', 'quantity' => 1]);
        $item->setRelation('product', $product);

        $order = new Order(['id' => 1]);
        $order->setRelation('items', collect([$item]));

        $gateway = app(DigiPayGateway::class);
        $method = new \ReflectionMethod(DigiPayGateway::class, 'deliverProductTitles');
        $method->setAccessible(true);

        $this->assertSame(['سینک ظرفشویی'], $method->invoke($gateway, $order));
    }
}
