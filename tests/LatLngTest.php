<?php

namespace Forup\EasyLeaflet\Tests;

use Forup\EasyLeaflet\LatLng;
use PHPUnit\Framework\TestCase;

class LatLngTest extends TestCase
{
    public function testToArrayReturnsLatAndLng()
    {
        $latLng = new LatLng(50.5, 14.25);

        $this->assertSame(array('lat' => 50.5, 'lng' => 14.25), $latLng->toArray());
    }

    public function testCastsNumericStringsToFloat()
    {
        $latLng = new LatLng('50.5', '14.25');

        $this->assertSame(50.5, $latLng->getLat());
        $this->assertSame(14.25, $latLng->getLng());
    }
}
