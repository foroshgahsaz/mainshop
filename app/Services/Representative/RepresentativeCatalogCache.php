<?php

namespace App\Services\Representative;

use Illuminate\Support\Facades\Cache;

class RepresentativeCatalogCache
{
    private const INDEX_KEY = 'rep:catalog:index';

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $this->registerKey($key);

        if ($this->supportsTags()) {
            return Cache::tags(['rep_catalog'])->remember($key, $ttl, $callback);
        }

        return Cache::remember($key, $ttl, $callback);
    }

    public function flush(): void
    {
        if ($this->supportsTags()) {
            Cache::tags(['rep_catalog'])->flush();
            Cache::forget(self::INDEX_KEY);

            return;
        }

        $keys = Cache::get(self::INDEX_KEY, []);

        foreach ($keys as $key) {
            if (is_string($key) && $key !== '') {
                Cache::forget($key);
            }
        }

        Cache::forget(self::INDEX_KEY);
    }

    private function registerKey(string $key): void
    {
        if ($this->supportsTags()) {
            return;
        }

        $index = Cache::get(self::INDEX_KEY, []);

        if (! in_array($key, $index, true)) {
            $index[] = $key;
            Cache::put(self::INDEX_KEY, $index, 86400);
        }
    }

    private function supportsTags(): bool
    {
        return method_exists(Cache::getStore(), 'tags');
    }
}
