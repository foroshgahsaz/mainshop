<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ShopUrlRedirect extends Model
{
    protected $table = 'shop_url_redirects';

    protected $fillable = [
        'from_path',
        'to_target',
        'status_code',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'status_code' => 'integer',
        ];
    }

    public const CACHE_KEY = 'shop:url_redirects:active:v1';

    protected static function booted(): void
    {
        static::saving(function (ShopUrlRedirect $redirect): void {
            $redirect->from_path = self::normalizeFromPath((string) $redirect->from_path);
            $redirect->to_target = trim((string) $redirect->to_target);
            $code = (int) $redirect->status_code;
            if (! in_array($code, [301, 302, 303, 307, 308], true)) {
                $redirect->status_code = 301;
            }
        });

        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public static function normalizeFromPath(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '/';
        }
        if (preg_match('#^https?://#i', $raw) === 1) {
            $path = parse_url($raw, PHP_URL_PATH);
            $raw = is_string($path) && $path !== '' ? $path : '/';
        }
        $raw = '/'.ltrim($raw, '/');

        return $raw === '//' ? '/' : $raw;
    }

    public function resolveRedirectResponse(): \Illuminate\Http\RedirectResponse
    {
        $target = trim($this->to_target);
        if ($target === '') {
            abort(500, 'Redirect target empty');
        }
        if (preg_match('#^https?://#i', $target) === 1) {
            return redirect()->away($target, $this->status_code);
        }

        $path = '/'.ltrim($target, '/');

        return redirect()->to($path === '//' ? '/' : $path, $this->status_code);
    }

    /**
     * @return \Illuminate\Support\Collection<string, self>
     */
    public static function activeMapByPath(): \Illuminate\Support\Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): \Illuminate\Support\Collection {
            return self::query()
                ->where('is_active', true)
                ->get()
                ->keyBy(fn (self $r) => $r->from_path);
        });
    }
}
