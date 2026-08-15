<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class RichContentSeeder extends Seeder
{
    public function run(): void
    {
        $asset = fn (string $path): string => asset($path);

        Page::updateOrCreate(['slug' => 'about'], [
            'title' => 'درباره ما',
            'meta_title' => 'درباره چاپینو | فروشگاه اینترنتی',
            'meta_description' => 'آشنایی با تیم، ارزش‌ها و خدمات فروشگاه اینترنتی چاپینو',
            'is_active' => true,
            'content' => <<<HTML
<h2>چاپینو؛ خرید آنلاین با اطمینان</h2>
<p>چاپینو از سال ۱۳۹۸ با هدف ساده‌سازی خرید آنلاین برای خانواده‌های ایرانی فعالیت می‌کند. ما باور داریم خرید باید <strong>سریع، شفاف و مطمئن</strong> باشد — از انتخاب محصول تا تحویل درب منزل.</p>

<figure>
    <img src="{$asset('shop/images/hero/slide-discount.svg')}" alt="فروشگاه چاپینو">
    <figcaption>نمایی از تجربه خرید در چاپینو</figcaption>
</figure>

<h3>ارزش‌های ما</h3>
<ul>
    <li><strong>اصالت کالا:</strong> تمام محصولات از تامین‌کنندگان معتبر تهیه می‌شوند.</li>
    <li><strong>پشتیبانی واقعی:</strong> تیم پشتیبانی شنبه تا پنج‌شنبه پاسخگوی شماست.</li>
    <li><strong>ارسال سریع:</strong> بسته‌بندی استاندارد و ارسال به سراسر کشور.</li>
    <li><strong>۷ روز بازگشت:</strong> امکان مرجوعی کالاهای سالم طبق قوانین.</li>
</ul>

<blockquote>ماموریت ما این است که هر خرید آنلاین، تجربه‌ای لذت‌بخش و بدون استرس باشد.</blockquote>

<h3>آمار و دستاوردها</h3>
<table>
    <thead>
        <tr><th>شاخص</th><th>مقدار</th><th>توضیح</th></tr>
    </thead>
    <tbody>
        <tr><td>مشتریان فعال</td><td>+۵۰,۰۰۰</td><td>کاربران ثبت‌نام‌شده</td></tr>
        <tr><td>محصولات</td><td>+۱,۲۰۰</td><td>در دسته‌های مختلف</td></tr>
        <tr><td>رضایت مشتری</td><td>۴.۸ از ۵</td><td>بر اساس نظرسنجی</td></tr>
        <tr><td>شهرهای تحت پوشش</td><td>۳۱ استان</td><td>ارسال سراسری</td></tr>
    </tbody>
</table>

<div class="info-box">
    <strong>می‌دانید؟</strong> تیم چاپینو متشکل از متخصصان فناوری، لجستیک و تجربه مشتری است که هر روز برای بهبود خدمات تلاش می‌کنند.
</div>
HTML,
        ]);

        Page::updateOrCreate(['slug' => 'contact'], [
            'title' => 'تماس با ما',
            'meta_title' => 'تماس با ما | چاپینو',
            'meta_description' => 'راه‌های ارتباط با پشتیبانی، فروش و واحد فنی فروشگاه چاپینو',
            'is_active' => true,
            'content' => <<<HTML
<p>برای پیگیری سفارش، پیشنهاد، انتقاد یا همکاری با ما در ارتباط باشید. تیم پشتیبانی در ساعات کاری پاسخگوی شماست.</p>

<h3>راه‌های ارتباطی</h3>
<table>
    <thead>
        <tr><th>بخش</th><th>راه ارتباط</th><th>ساعات پاسخگویی</th></tr>
    </thead>
    <tbody>
        <tr><td>پشتیبانی سفارش</td><td>۰۲۱-۹۱۰۰۰۰۰۰</td><td>شنبه تا پنج‌شنبه ۹–۱۸</td></tr>
        <tr><td>واتساپ</td><td>۰۹۱۲-۰۰۰۰۰۰۰</td><td>۹–۲۱</td></tr>
        <tr><td>ایمیل</td><td>support@chapino.ir</td><td>۲۴ ساعته (پاسخ در ۲۴h)</td></tr>
        <tr><td>همکاری تجاری</td><td>biz@chapino.ir</td><td>شنبه تا چهارشنبه</td></tr>
    </tbody>
</table>

<h3>آدرس دفتر مرکزی</h3>
<p>تهران، خیابان ولیعصر، بالاتر از پارک ساعی، پلاک ۱۲۳۴، واحد ۵<br>کد پستی: ۱۴۱۵۹۸۴۳۵۱</p>

<figure>
    <img src="{$asset('shop/images/hero/slide-ai.svg')}" alt="دفتر چاپینو">
    <figcaption>دفتر مرکزی و مرکز پشتیبانی</figcaption>
</figure>

<div class="warning-box">
    <strong>توجه:</strong> برای پیگیری سفارش، لطفاً <strong>شماره سفارش</strong> یا <strong>شماره موبایل</strong> ثبت‌شده را آماده داشته باشید.
</div>

<h3>سوالات پرتکرار</h3>
<ol>
    <li>زمان ارسال بسته به شهر مقصد ۲ تا ۵ روز کاری است.</li>
    <li>پرداخت آنلاین از درگاه امن زرین‌پال انجام می‌شود.</li>
    <li>مرجوعی کالا تا ۷ روز پس از تحویل امکان‌پذیر است.</li>
</ol>
HTML,
        ]);

        $author = User::query()->where('is_author', true)->first()
            ?? User::query()->where('is_admin', true)->first();

        Post::updateOrCreate(['slug' => 'complete-shopping-guide-chapino'], [
            'title' => 'راهنمای جامع خرید آنلاین از چاپینو — تست کامل محتوا',
            'user_id' => $author?->id,
            'excerpt' => 'راهنمای کامل خرید آنلاین: از انتخاب محصول تا پرداخت، ارسال، مرجوعی و نکات مهم — شامل جدول، تصویر، لیست و نقل‌قول.',
            'image' => null,
            'is_active' => true,
            'published_at' => now(),
            'meta_title' => 'راهنمای جامع خرید آنلاین | مجله چاپینو',
            'meta_description' => 'آموزش گام‌به‌گام خرید از فروشگاه چاپینو با مثال‌های عملی، جدول مقایسه و نکات کاربردی.',
            'content' => <<<HTML
<h2>مقدمه</h2>
<p>خرید آنلاین وقتی لذت‌بخش است که <strong>شفاف، سریع و قابل اعتماد</strong> باشد. در این مقاله تمام مراحل خرید از چاپینو را با جزئیات بررسی می‌کنیم — از جستجوی محصول تا دریافت بسته درب منزل.</p>

<figure>
    <img src="{$asset('shop/images/blog/article-1.svg')}" alt="خرید آنلاین">
    <figcaption>تصویر شاخص — خرید آنلاین آسان</figcaption>
</figure>

<h2>۱. جستجو و انتخاب محصول</h2>
<p>از نوار جستجو، دسته‌بندی‌ها یا فیلترهای قیمت و برند استفاده کنید. در صفحه محصول می‌توانید:</p>
<ul>
    <li>تصاویر گالری را بزرگنمایی کنید</li>
    <li>variant (رنگ، سایز) را انتخاب کنید</li>
    <li>نظرات و پرسش‌های دیگر خریداران را بخوانید</li>
    <li>مشخصات فنی را در تب «مشخصات» ببینید</li>
</ul>

<h3>مقایسه روش‌های پرداخت</h3>
<table>
    <thead>
        <tr><th>روش</th><th>سرعت</th><th>امنیت</th><th>مناسب برای</th></tr>
    </thead>
    <tbody>
        <tr><td>پرداخت آنلاین</td><td>فوری</td><td>بالا</td><td>خرید فوری</td></tr>
        <tr><td>پرداخت در محل</td><td>هنگام تحویل</td><td>متوسط</td><td>شهرهای تحت پوشش</td></tr>
        <tr><td>کیف پول</td><td>فوری</td><td>بالا</td><td>خریدهای مکرر</td></tr>
    </tbody>
</table>

<h2>۲. سبد خرید و تسویه حساب</h2>
<p>پس از افزودن به سبد، تعداد را تنظیم کنید و به صفحه تسویه بروید. در checkout:</p>
<ol>
    <li>آدرس تحویل را انتخاب کنید</li>
    <li>روش ارسال را مشخص کنید</li>
    <li>در صورت داشتن، کد تخفیف وارد کنید</li>
    <li>روش پرداخت را انتخاب و سفارش را ثبت کنید</li>
</ol>

<blockquote>نکته طلایی: قبل از پرداخت، خلاصه سفارش و مبلغ نهایی را حتماً بررسی کنید.</blockquote>

<div class="content-grid">
    <figure>
        <img src="{$asset('shop/images/products/clothing.svg')}" alt="پوشاک">
        <figcaption>دسته پوشاک</figcaption>
    </figure>
    <figure>
        <img src="{$asset('shop/images/products/fan.svg')}" alt="لوازم خانگی">
        <figcaption>لوازم خانگی</figcaption>
    </figure>
</div>

<h2>۳. ارسال و تحویل</h2>
<p>پس از ثبت سفارش، پیامک تأیید دریافت می‌کنید. وضعیت سفارش از پنل کاربری قابل پیگیری است.</p>

<div class="info-box">
    <strong>ارسال رایگان:</strong> برای سفارش‌های بالای ۵۰۰,۰۰۰ تومان در تهران و شهرهای منتخب.
</div>

<h2>۴. مرجوعی و گارانتی</h2>
<p>تا ۷ روز فرصت دارید کالای سالم را مرجوع کنید. جزئیات در صفحه <a href="/pages/refund-policy">شرایط بازگشت کالا</a> آمده است.</p>

<h3>چک‌لیست قبل از خرید</h3>
<table>
    <thead>
        <tr><th>مورد</th><th>بررسی شد؟</th></tr>
    </thead>
    <tbody>
        <tr><td>مشخصات محصول با نیاز من همخوان است</td><td>☐</td></tr>
        <tr><td>قیمت و تخفیف را مقایسه کردم</td><td>☐</td></tr>
        <tr><td>آدرس و شماره تماس درست است</td><td>☐</td></tr>
        <tr><td>روش پرداخت مناسب را انتخاب کردم</td><td>☐</td></tr>
    </tbody>
</table>

<h2>جمع‌بندی</h2>
<p>با رعایت این نکات، تجربه خرید شما از چاپینو <strong>سریع، مطمئن و لذت‌بخش</strong> خواهد بود. سوالی دارید؟ به صفحه <a href="/pages/contact">تماس با ما</a> سر بزنید.</p>
HTML,
        ]);

        $this->command?->info('✅ RichContentSeeder با موفقیت اجرا شد.');

        \App\Models\Product::query()->where('slug', 'demo-product-1')->update([
            'description' => <<<HTML
<h2>معرفی محصول</h2>
<p>این محصول نمونه برای <strong>تست نمایش Rich Editor</strong> در صفحه محصول است.</p>
<ul>
    <li>کیفیت ساخت بالا</li>
    <li>گارانتی ۱۸ ماهه</li>
    <li>ارسال سریع</li>
</ul>
<table>
    <tr><th>جنس</th><td>پارچه درجه یک</td></tr>
    <tr><th>کشور سازنده</th><td>ایران</td></tr>
</table>
HTML,
        ]);
    }
}
