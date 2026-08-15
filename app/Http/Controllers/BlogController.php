<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\Cache\ShopCacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(
        protected ShopCacheService $cache
    ) {}

    public function index(): View
    {
        $posts = $this->cache->blogPosts();

        return view('shop.blog.index', compact('posts'));
    }

    public function show(Post $post): View
    {
        abort_unless($post->is_active && ($post->published_at === null || $post->published_at <= now()), 404);

        $post->load('author');

        Cache::remember(
            'shop:post:views:'.$post->id.':'.request()->ip(),
            3600,
            function () use ($post) {
                $post->increment('views');

                return true;
            }
        );

        $relatedPosts = Post::published()
            ->where('id', '!=', $post->id)
            ->latest('published_at')
            ->take(4)
            ->get();

        return view('shop.blog.show', compact('post', 'relatedPosts'));
    }
}
