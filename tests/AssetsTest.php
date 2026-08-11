<?php

namespace Forup\EasyLeaflet\Tests;

use Forup\EasyLeaflet\Assets;
use Forup\EasyLeaflet\EasyLeafletException;
use PHPUnit\Framework\TestCase;

class AssetsTest extends TestCase
{
    public function testRendersDefaultLeafletCdnLinkAndScriptTags()
    {
        $assets = new Assets('/js/easy-leaflet.js');
        $html = $assets->render();

        $this->assertStringContainsString(
            '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">',
            $html
        );
        $this->assertStringContainsString(
            '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>',
            $html
        );
        $this->assertStringContainsString('<script src="/js/easy-leaflet.js"></script>', $html);
    }

    public function testAllowsOverridingLeafletCoreUrls()
    {
        $assets = new Assets('/js/easy-leaflet.js', '/vendor-assets/leaflet.css', '/vendor-assets/leaflet.js');
        $html = $assets->render();

        $this->assertStringContainsString('<link rel="stylesheet" href="/vendor-assets/leaflet.css">', $html);
        $this->assertStringContainsString('<script src="/vendor-assets/leaflet.js"></script>', $html);
    }

    public function testThrowsWithoutEasyLeafletJsUrl()
    {
        $this->expectException(EasyLeafletException::class);

        new Assets('');
    }
}
