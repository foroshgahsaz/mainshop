<?php

namespace Tests\Unit;

use App\Support\StoragePermissionFixer;
use Tests\TestCase;

class StoragePermissionFixerTest extends TestCase
{
    public function test_web_user_honors_shop_web_user_env_config(): void
    {
        config([
            'shop.storage.web_user' => 'xfs',
            'shop.storage.web_group' => 'xfs',
        ]);

        $this->assertSame('xfs', StoragePermissionFixer::webUser());
        $this->assertSame('xfs', StoragePermissionFixer::webGroup());
    }
}
