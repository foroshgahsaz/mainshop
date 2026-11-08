<?php

namespace App\Services\Shop;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تعمیر اسکیما وقتی migrate در migrations ثبت شده اما جدول/ستون ساخته نشده.
 */
final class MegaMenuSchemaRepair
{
    /** @return list<string> */
    public function ensure(): array
    {
        $actions = [];

        if (Schema::hasTable('product_families') && ! Schema::hasColumn('product_families', 'show_in_mega')) {
            Schema::table('product_families', function (Blueprint $table): void {
                $table->boolean('show_in_mega')->default(false)->after('is_active');
            });
            $actions[] = 'product_families.show_in_mega';
        }

        if (Schema::hasTable('categories') && ! Schema::hasColumn('categories', 'product_family_id')) {
            Schema::table('categories', function (Blueprint $table): void {
                $table->unsignedBigInteger('product_family_id')->nullable()->after('parent_id');
                $table->index('product_family_id');
            });
            $actions[] = 'categories.product_family_id';
        }

        if (! Schema::hasTable('mega_menu_links')) {
            Schema::create('mega_menu_links', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('root_category_id')->nullable();
                $table->unsignedBigInteger('product_family_id')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->string('label');
                $table->string('url', 2048);
                $table->unsignedInteger('position')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('admin_shop_preview')->default(false);
                $table->timestamps();

                $table->index('root_category_id');
                $table->index(['product_family_id', 'position']);
                $table->index('parent_id');
                $table->index('category_id');
            });
            $actions[] = 'mega_menu_links (created)';
        } elseif (! Schema::hasColumn('mega_menu_links', 'root_category_id')) {
            Schema::table('mega_menu_links', function (Blueprint $table): void {
                $table->unsignedBigInteger('root_category_id')->nullable()->after('id');
                $table->index('root_category_id');
            });
            $actions[] = 'mega_menu_links.root_category_id';
        }

        return $actions;
    }
}
