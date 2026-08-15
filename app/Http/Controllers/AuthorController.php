<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function show(User $author): View
    {
        abort_unless($author->is_author && $author->slug, 404);

        $posts = Post::published()
            ->where('user_id', $author->id)
            ->latest('published_at')
            ->paginate(12);

        return view('shop.authors.show', compact('author', 'posts'));
    }
}
