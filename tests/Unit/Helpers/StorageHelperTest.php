<?php

namespace Tests\Unit\Helpers;

use App\Helpers\StorageHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class StorageHelperTest extends TestCase
{
    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_it_builds_public_disk_urls(): void
    {
        Cache::flush();

        $url = StorageHelper::getFileUrl('public', 'images/logo.png');

        $this->assertSame('/storage/images/logo.png', $url);
    }

    public function test_it_builds_idcloudhost_urls_from_endpoint_configuration(): void
    {
        Cache::flush();

        Config::set('filesystems.disks.idcloudhost.bucket', 'learning-assets');
        Config::set('filesystems.disks.idcloudhost.endpoint', 'https://cdn.csi-academy.id');
        Config::set('filesystems.disks.idcloudhost.region', 'ap-southeast-1');

        $url = StorageHelper::getFileUrl('idcloudhost', 'videos/intro.mp4');

        $this->assertSame('https://cdn.csi-academy.id/learning-assets/videos/intro.mp4', $url);
    }

    public function test_cached_file_urls_are_reused(): void
    {
        Cache::flush();

        Config::set('filesystems.disks.idcloudhost.bucket', 'learning-assets');
        Config::set('filesystems.disks.idcloudhost.endpoint', 'https://cdn.csi-academy.id');
        Config::set('filesystems.disks.idcloudhost.region', 'ap-southeast-1');

        $first = StorageHelper::getFileUrl('idcloudhost', 'videos/intro.mp4');

        Config::set('filesystems.disks.idcloudhost.endpoint', 'https://changed.example.com');

        $second = StorageHelper::getFileUrl('idcloudhost', 'videos/intro.mp4');

        $this->assertSame($first, $second);
    }
}
