<?php

namespace Forup\EasyLeaflet\Tests;

use Forup\EasyLeaflet\EasyLeafletException;
use Forup\EasyLeaflet\TileLayer;
use PHPUnit\Framework\TestCase;

class TileLayerTest extends TestCase
{
    public function testOpenStreetMapPreset()
    {
        $data = TileLayer::openStreetMap()->toArray();

        $this->assertSame('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', $data['url']);
        $this->assertStringContainsString('OpenStreetMap', $data['attribution']);
    }

    public function testCartoVoyagerPreset()
    {
        $data = TileLayer::cartoVoyager()->toArray();

        $this->assertStringContainsString('voyager', $data['url']);
    }

    public function testCartoLightPreset()
    {
        $data = TileLayer::cartoLight()->toArray();

        $this->assertStringContainsString('light_all', $data['url']);
    }

    public function testCartoDarkPreset()
    {
        $data = TileLayer::cartoDark()->toArray();

        $this->assertStringContainsString('dark_all', $data['url']);
    }

    public function testMapyCzEncodesApiKeyIntoUrl()
    {
        $data = TileLayer::mapyCz('secret-key')->toArray();

        $this->assertStringContainsString('apikey=secret-key', $data['url']);
    }

    public function testMapyCzThrowsWithoutApiKey()
    {
        $this->expectException(EasyLeafletException::class);

        TileLayer::mapyCz('');
    }
}
