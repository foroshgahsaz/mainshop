<?php

use App\Services\Settings\SettingsService;

if (! function_exists('site_name')) {
    function site_name(): string
    {
        return app(SettingsService::class)->site()['name'];
    }
}
