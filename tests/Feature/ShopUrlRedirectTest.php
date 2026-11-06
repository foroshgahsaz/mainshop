<?php

namespace Tests\Feature;

use App\Models\ShopUrlRedirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopUrlRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirects_old_path_to_target(): void
    {
        ShopUrlRedirect::create([
            'from_path' => '/old-page',
            'to_target' => '/blog',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->get('/old-page')->assertRedirect('/blog');
    }
}
