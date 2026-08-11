<?php

namespace Forup\EasyLeaflet\Tests;

use Forup\EasyLeaflet\Icon;
use Forup\EasyLeaflet\LatLng;
use Forup\EasyLeaflet\Marker;
use Forup\EasyLeaflet\Popup;
use PHPUnit\Framework\TestCase;

class MarkerTest extends TestCase
{
    public function testToArrayWithIconAndPopup()
    {
        $marker = new Marker(
            new LatLng(50.0, 14.0),
            new Icon('/img/pin.png', 10, 10),
            Popup::text('Hello')
        );
        $data = $marker->toArray();

        $this->assertSame(array('lat' => 50.0, 'lng' => 14.0), $data['latlng']);
        $this->assertSame('/img/pin.png', $data['icon']['iconUrl']);
        $this->assertSame('Hello', $data['popup']);
    }

    public function testToArrayWithoutIconAndPopup()
    {
        $marker = new Marker(new LatLng(50.0, 14.0));
        $data = $marker->toArray();

        $this->assertArrayNotHasKey('icon', $data);
        $this->assertArrayNotHasKey('popup', $data);
    }

    public function testGetLatLngReturnsTheSameInstance()
    {
        $latLng = new LatLng(50.0, 14.0);
        $marker = new Marker($latLng);

        $this->assertSame($latLng, $marker->getLatLng());
    }
}
