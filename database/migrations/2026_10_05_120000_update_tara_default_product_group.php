<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $group = Setting::query()
            ->where('group', 'tara')
            ->where('key', 'default_group')
            ->first();

        if ($group === null || in_array($group->value, ['', '1'], true)) {
            Setting::updateOrCreate(
                ['group' => 'tara', 'key' => 'default_group'],
                ['value' => '15']
            );
        }

        $title = Setting::query()
            ->where('group', 'tara')
            ->where('key', 'default_group_title')
            ->first();

        if ($title === null || in_array($title->value, ['', 'عمومی'], true)) {
            Setting::updateOrCreate(
                ['group' => 'tara', 'key' => 'default_group_title'],
                ['value' => 'خانه و آشپزخانه']
            );
        }
    }

    public function down(): void
    {
        Setting::query()
            ->where('group', 'tara')
            ->where('key', 'default_group')
            ->where('value', '15')
            ->update(['value' => '1']);

        Setting::query()
            ->where('group', 'tara')
            ->where('key', 'default_group_title')
            ->where('value', 'خانه و آشپزخانه')
            ->update(['value' => 'عمومی']);
    }
};
