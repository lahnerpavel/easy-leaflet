<?php

namespace Forup\EasyLeaflet\Tests;

use Forup\EasyLeaflet\EasyLeafletException;
use Forup\EasyLeaflet\LatLng;
use Forup\EasyLeaflet\Map;
use Forup\EasyLeaflet\Marker;
use PHPUnit\Framework\TestCase;

class MapTest extends TestCase
{
    public function testThrowsWhenNoMarkersAndNoExplicitCenter()
    {
        $this->expectException(EasyLeafletException::class);

        Map::create()->render();
    }

    public function testSingleMarkerUsesCenterModeWithDefaultZoom()
    {
        $html = Map::create()
            ->addMarker(new Marker(new LatLng(50.0, 14.0)))
            ->render();

        $this->assertStringContainsString('"mode":"center"', $html);
        $this->assertStringContainsString('"zoom":15', $html);
    }

    public function testMultipleMarkersUseBoundsMode()
    {
        $html = Map::create()
            ->addMarker(new Marker(new LatLng(50.0, 14.0)))
            ->addMarker(new Marker(new LatLng(49.0, 16.0)))
            ->render();

        $this->assertStringContainsString('"mode":"bounds"', $html);
    }

    public function testExplicitCenterOverridesAutoModeEvenWithMultipleMarkers()
    {
        $html = Map::create()
            ->addMarker(new Marker(new LatLng(50.0, 14.0)))
            ->addMarker(new Marker(new LatLng(49.0, 16.0)))
            ->setCenter(50.0, 14.0)
            ->setZoom(10)
            ->render();

        $this->assertStringContainsString('"mode":"center"', $html);
        $this->assertStringContainsString('"zoom":10', $html);
    }

    public function testExplicitCenterWithoutMarkersUsesDefaultZoom()
    {
        $html = Map::create()
            ->setCenter(50.0, 14.0)
            ->render();

        $this->assertStringContainsString('"mode":"center"', $html);
        $this->assertStringContainsString('"zoom":13', $html);
    }

    public function testBoundsModeHasNoMaxZoomByDefault()
    {
        $html = Map::create()
            ->addMarker(new Marker(new LatLng(50.0, 14.0)))
            ->addMarker(new Marker(new LatLng(49.0, 16.0)))
            ->render();

        $this->assertStringContainsString('"mode":"bounds","maxZoom":null', $html);
    }

    public function testSetMaxZoomAppliesInBoundsMode()
    {
        $html = Map::create()
            ->addMarker(new Marker(new LatLng(50.0, 14.0)))
            ->addMarker(new Marker(new LatLng(49.0, 16.0)))
            ->setMaxZoom(10)
            ->render();

        $this->assertStringContainsString('"maxZoom":10', $html);
    }

    public function testEachMapGetsAUniqueContainerIdByDefault()
    {
        $htmlA = Map::create()->addMarker(new Marker(new LatLng(50.0, 14.0)))->render();
        $htmlB = Map::create()->addMarker(new Marker(new LatLng(50.0, 14.0)))->render();

        preg_match('/<div id="([^"]+)">/', $htmlA, $matchesA);
        preg_match('/<div id="([^"]+)">/', $htmlB, $matchesB);

        $this->assertNotSame($matchesA[1], $matchesB[1]);
    }

    public function testSetContainerIdOverridesTheGeneratedId()
    {
        $html = Map::create()
            ->setContainerId('my-map')
            ->addMarker(new Marker(new LatLng(50.0, 14.0)))
            ->render();

        $this->assertStringContainsString('<div id="my-map"></div>', $html);
    }
}
