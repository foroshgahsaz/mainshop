<?php

namespace App\Observers;

use App\Models\HomeSlider;
use Illuminate\Support\Facades\Cache;

class HomeSliderObserver
{
    public function saved(HomeSlider $slider): void
    {
        $this->flush();
    }

    public function deleted(HomeSlider $slider): void
    {
        $this->flush();
    }

    protected function flush(): void
    {
        Cache::forget('shop:home:sliders');
        Cache::forget('shop:home:payload');

        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags(['shop', 'home'])->flush();
        }
    }
}
