<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SampleOrdersSeeder extends Seeder
{
    public const SAMPLE_PASSWORD = 'password';

    public function run(): void
    {
        $shipping = ShippingMethod::where('name', 'پست پیشتاز')->first();
        if (! $shipping) {
            $this->command?->error('روش ارسال «پست پیشتاز» یافت نشد. ابتدا ShopDemoSeeder را اجرا کنید.');

            return;
        }

        $shirt = Product::where('slug', 'classic-mens-shirt')->first();
        $shoe = Product::where('slug', 'leather-sport-shoe')->first();
        $demo1 = Product::where('slug', 'demo-product-1')->first();
        $demo5 = Product::where('slug', 'demo-product-5')->first();
        $demo10 = Product::where('slug', 'demo-product-10')->first();

        if (! $shirt || ! $shoe || ! $demo1) {
            $this->command?->error('محصولات نمونه یافت نشدند. ابتدا ShopDemoSeeder و SampleProductsSeeder را اجرا کنید.');

            return;
        }

        $shirtVariant = ProductVariant::where('product_id', $shirt->id)->first();
        $shoeVariant = ProductVariant::where('product_id', $shoe->id)->skip(1)->first()
            ?? ProductVariant::where('product_id', $shoe->id)->first();

        $customers = $this->customersConfig(
            $shipping,
            $shirt,
            $shoe,
            $demo1,
            $demo5 ?? $demo1,
            $demo10 ?? $demo1,
            $shirtVariant,
            $shoeVariant,
        );

        foreach ($customers as $customer) {
            $user = User::updateOrCreate(
                ['phone' => $customer['phone']],
                [
                    'name' => $customer['name'],
                    'email' => $customer['email'],
                    'password' => Hash::make(self::SAMPLE_PASSWORD),
                    'phone_verified_at' => now()->subMonths(2),
                    'status' => true,
                    'is_admin' => false,
                ]
            );

            $address = UserAddress::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'receiver_phone' => $customer['phone'],
                ],
                [
                    'receiver_name' => $customer['name'],
                    'province' => $customer['address']['province'],
                    'city' => $customer['address']['city'],
                    'address' => $customer['address']['address'],
                    'postal_code' => $customer['address']['postal_code'],
                    'is_default' => true,
                ]
            );

            foreach ($customer['orders'] as $orderData) {
                $this->seedOrder($user, $address, $shipping, $orderData);
            }
        }

        $this->command?->newLine();
        $this->command?->info('✅ SampleOrdersSeeder: ۴ کاربر با ۸ سفارش و پرداخت‌های نمونه ساخته شد.');
        $this->command?->info('   رمز عبور همه کاربران: '.self::SAMPLE_PASSWORD);
        $this->command?->table(
            ['نام', 'موبایل', 'ایمیل', 'سفارش‌ها'],
            collect($customers)->map(fn (array $c) => [
                $c['name'],
                $c['phone'],
                $c['email'],
                count($c['orders']),
            ])->all()
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function customersConfig(
        ShippingMethod $shipping,
        Product $shirt,
        Product $shoe,
        Product $demo1,
        Product $demo5,
        Product $demo10,
        ?ProductVariant $shirtVariant,
        ?ProductVariant $shoeVariant,
    ): array {
        $shippingPrice = (int) $shipping->price;

        return [
            [
                'name' => 'علی رضایی',
                'phone' => '09121111101',
                'email' => 'ali.rezaei@example.com',
                'address' => [
                    'province' => 'تهران',
                    'city' => 'تهران',
                    'address' => 'خیابان ولیعصر، کوچه ۱۲، پلاک ۴۵، واحد ۳',
                    'postal_code' => '1417812345',
                ],
                'orders' => [
                    [
                        'tracking_code' => 'SAMPLE-ALI-01',
                        'payment_method' => 'online',
                        'status' => Order::STATUS_DELIVERED,
                        'note' => 'لطفاً قبل از تحویل تماس بگیرید.',
                        'shipping_tracking_code' => 'POST-140401001',
                        'shipped_at' => now()->subDays(12),
                        'delivered_at' => now()->subDays(9),
                        'created_at' => now()->subDays(15),
                        'items' => [
                            $this->itemFromProduct($shirt, 2, $shirtVariant),
                            $this->itemFromProduct($demo1, 1),
                        ],
                        'discount_amount' => 50000,
                        'payment' => [
                            'status' => Payment::STATUS_SUCCESS,
                            'gateway' => 'zarinpal',
                            'transaction_id' => 'A00000000000000000000000000123456789',
                            'card_number' => '6037-****-****-1234',
                            'paid_at' => now()->subDays(15)->addMinutes(8),
                        ],
                    ],
                    [
                        'tracking_code' => 'SAMPLE-ALI-02',
                        'payment_method' => 'online',
                        'status' => Order::STATUS_PENDING,
                        'note' => null,
                        'created_at' => now()->subHours(6),
                        'items' => [
                            $this->itemFromProduct($shoe, 1, $shoeVariant),
                        ],
                        'payment' => [
                            'status' => Payment::STATUS_PENDING,
                            'gateway' => 'zarinpal',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'مریم احمدی',
                'phone' => '09121111102',
                'email' => 'maryam.ahmadi@example.com',
                'address' => [
                    'province' => 'اصفهان',
                    'city' => 'اصفهان',
                    'address' => 'خیابان چهارباغ عباسی، مجتمع نور، طبقه ۲',
                    'postal_code' => '8145678901',
                ],
                'orders' => [
                    [
                        'tracking_code' => 'SAMPLE-MRY-01',
                        'payment_method' => 'online',
                        'status' => Order::STATUS_SHIPPED,
                        'note' => 'ارسال در ساعات اداری',
                        'shipping_tracking_code' => 'POST-140401002',
                        'shipped_at' => now()->subDays(2),
                        'created_at' => now()->subDays(5),
                        'items' => [
                            $this->itemFromProduct($demo5, 3),
                            $this->itemFromProduct($demo10, 1),
                        ],
                        'payment' => [
                            'status' => Payment::STATUS_SUCCESS,
                            'gateway' => 'zarinpal',
                            'transaction_id' => 'A00000000000000000000000000234567890',
                            'card_number' => '6219-****-****-5678',
                            'paid_at' => now()->subDays(5)->addMinutes(12),
                        ],
                    ],
                    [
                        'tracking_code' => 'SAMPLE-MRY-02',
                        'payment_method' => 'cod',
                        'status' => Order::STATUS_PROCESSING,
                        'note' => 'پرداخت در محل',
                        'created_at' => now()->subDay(),
                        'items' => [
                            $this->itemFromProduct($shirt, 1, $shirtVariant),
                        ],
                        'payment' => null,
                    ],
                ],
            ],
            [
                'name' => 'حسین کریمی',
                'phone' => '09121111103',
                'email' => 'hossein.karimi@example.com',
                'address' => [
                    'province' => 'فارس',
                    'city' => 'شیراز',
                    'address' => 'بلوار زند، نبش کوچه گلستان، پلاک ۸',
                    'postal_code' => '7134567890',
                ],
                'orders' => [
                    [
                        'tracking_code' => 'SAMPLE-HSN-01',
                        'payment_method' => 'online',
                        'status' => Order::STATUS_DELIVERED,
                        'shipping_tracking_code' => 'POST-140401003',
                        'shipped_at' => now()->subDays(20),
                        'delivered_at' => now()->subDays(17),
                        'created_at' => now()->subDays(22),
                        'items' => [
                            $this->itemFromProduct($shoe, 1, $shoeVariant),
                            $this->itemFromProduct($demo1, 2),
                        ],
                        'payment' => [
                            'status' => Payment::STATUS_SUCCESS,
                            'gateway' => 'zarinpal',
                            'transaction_id' => 'A00000000000000000000000000345678901',
                            'card_number' => '5022-****-****-9012',
                            'paid_at' => now()->subDays(22)->addMinutes(5),
                        ],
                    ],
                    [
                        'tracking_code' => 'SAMPLE-HSN-02',
                        'payment_method' => 'online',
                        'status' => Order::STATUS_CANCELED,
                        'note' => 'لغو توسط کاربر — پرداخت ناموفق',
                        'created_at' => now()->subDays(3),
                        'items' => [
                            $this->itemFromProduct($demo5, 1),
                        ],
                        'payment' => [
                            'status' => Payment::STATUS_FAILED,
                            'gateway' => 'zarinpal',
                            'raw_response' => ['code' => -51, 'message' => 'تراکنش ناموفق'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'فاطمه محمدی',
                'phone' => '09121111104',
                'email' => 'fateme.mohammadi@example.com',
                'address' => [
                    'province' => 'خراسان رضوی',
                    'city' => 'مشهد',
                    'address' => 'بلوار سجاد، بین سجاد ۱۵ و ۱۷، پلاک ۲۲',
                    'postal_code' => '9187654321',
                ],
                'orders' => [
                    [
                        'tracking_code' => 'SAMPLE-FAT-01',
                        'payment_method' => 'cod',
                        'status' => Order::STATUS_DELIVERED,
                        'note' => 'تحویل به نگهبانی مجاز است',
                        'shipping_tracking_code' => 'POST-140401004',
                        'shipped_at' => now()->subDays(8),
                        'delivered_at' => now()->subDays(5),
                        'created_at' => now()->subDays(10),
                        'items' => [
                            $this->itemFromProduct($demo10, 2),
                            $this->itemFromProduct($shirt, 1, $shirtVariant),
                        ],
                        'payment' => null,
                    ],
                    [
                        'tracking_code' => 'SAMPLE-FAT-02',
                        'payment_method' => 'online',
                        'status' => Order::STATUS_PROCESSING,
                        'created_at' => now()->subHours(18),
                        'items' => [
                            $this->itemFromProduct($shoe, 1, $shoeVariant),
                            $this->itemFromProduct($demo1, 1),
                        ],
                        'discount_amount' => 100000,
                        'payment' => [
                            'status' => Payment::STATUS_SUCCESS,
                            'gateway' => 'zarinpal',
                            'transaction_id' => 'A00000000000000000000000000456789012',
                            'card_number' => '6274-****-****-3456',
                            'paid_at' => now()->subHours(18)->addMinutes(3),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $orderData
     */
    private function seedOrder(User $user, UserAddress $address, ShippingMethod $shipping, array $orderData): void
    {
        if (Order::where('tracking_code', $orderData['tracking_code'])->exists()) {
            return;
        }

        $itemsTotal = collect($orderData['items'])->sum('total_price');
        $discount = (int) ($orderData['discount_amount'] ?? 0);
        $shippingAmount = (int) $shipping->price;
        $finalAmount = $itemsTotal + $shippingAmount - $discount;

        $order = Order::create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'shipping_method_id' => $shipping->id,
            'total_amount' => $itemsTotal,
            'final_amount' => $finalAmount,
            'shipping_amount' => $shippingAmount,
            'discount_amount' => $discount,
            'payment_method' => $orderData['payment_method'],
            'status' => $orderData['status'],
            'tracking_code' => $orderData['tracking_code'],
            'shipping_tracking_code' => $orderData['shipping_tracking_code'] ?? null,
            'note' => $orderData['note'] ?? null,
            'shipped_at' => $orderData['shipped_at'] ?? null,
            'delivered_at' => $orderData['delivered_at'] ?? null,
            'created_at' => $orderData['created_at'] ?? now(),
            'updated_at' => $orderData['created_at'] ?? now(),
        ]);

        foreach ($orderData['items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                ...$item,
            ]);
        }

        if (! empty($orderData['payment'])) {
            $payment = $orderData['payment'];
            Payment::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'amount' => $finalAmount,
                'gateway' => $payment['gateway'] ?? config('payment.default', 'zarinpal'),
                'status' => $payment['status'],
                'tracking_code' => strtoupper(Str::random(12)),
                'transaction_id' => $payment['transaction_id'] ?? null,
                'card_number' => $payment['card_number'] ?? null,
                'paid_at' => $payment['paid_at'] ?? null,
                'raw_response' => $payment['raw_response'] ?? null,
                'created_at' => $order->created_at,
                'updated_at' => $order->created_at,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function itemFromProduct(Product $product, int $quantity, ?ProductVariant $variant = null): array
    {
        if ($variant && $variant->product_id === $product->id) {
            $price = (int) ($variant->sale_price ?? $variant->price ?? $product->sale_price ?? $product->price);

            return [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
                'price' => $price,
                'total_price' => $price * $quantity,
                'product_name' => $product->name.' — '.$variant->name,
                'sku' => $variant->sku ?? $product->sku,
            ];
        }

        $price = (int) ($product->sale_price ?? $product->price);

        return [
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => $quantity,
            'price' => $price,
            'total_price' => $price * $quantity,
            'product_name' => $product->name,
            'sku' => $product->sku,
        ];
    }
}
