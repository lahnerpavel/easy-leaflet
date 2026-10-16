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

    public function testCartoPresetsWithoutApiKeyHaveNoKeyParameter()
    {
        $this->assertStringEndsWith('{r}.png', TileLayer::cartoVoyager()->toArray()['url']);
        $this->assertStringEndsWith('{r}.png', TileLayer::cartoLight()->toArray()['url']);
        $this->assertStringEndsWith('{r}.png', TileLayer::cartoDark()->toArray()['url']);
    }

    public function testCartoPresetsEncodeApiKeyIntoUrl()
    {
        $this->assertStringEndsWith('voyager/{z}/{x}/{y}{r}.png?key=secret%20key', TileLayer::cartoVoyager('secret key')->toArray()['url']);
        $this->assertStringEndsWith('light_all/{z}/{x}/{y}{r}.png?key=secret%20key', TileLayer::cartoLight('secret key')->toArray()['url']);
        $this->assertStringEndsWith('dark_all/{z}/{x}/{y}{r}.png?key=secret%20key', TileLayer::cartoDark('secret key')->toArray()['url']);
    }

    public function testCartoPresetsIgnoreEmptyApiKey()
    {
        $this->assertStringEndsWith('{r}.png', TileLayer::cartoLight('')->toArray()['url']);
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
