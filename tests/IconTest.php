<?php

namespace Forup\EasyLeaflet\Tests;

use Forup\EasyLeaflet\EasyLeafletException;
use Forup\EasyLeaflet\Icon;
use PHPUnit\Framework\TestCase;

class IconTest extends TestCase
{
    public function testToArrayIncludesOnlySetFields()
    {
        $icon = new Icon('/img/pin.png', 32, 40);

        $this->assertSame(
            array('iconUrl' => '/img/pin.png', 'iconSize' => array(32, 40)),
            $icon->toArray()
        );
    }

    public function testCenterBottomComputesAnchorsFromSize()
    {
        $icon = Icon::centerBottom('/img/pin.png', 32, 40);
        $data = $icon->toArray();

        $this->assertSame(array(16, 40), $data['iconAnchor']);
        $this->assertSame(array(0, -40), $data['popupAnchor']);
    }

    public function testSetShadowAddsShadowFields()
    {
        $icon = (new Icon('/img/pin.png'))->setShadow('/img/pin-shadow.png', 40, 20);
        $data = $icon->toArray();

        $this->assertSame('/img/pin-shadow.png', $data['shadowUrl']);
        $this->assertSame(array(40, 20), $data['shadowSize']);
    }

    public function testThrowsOnEmptyIconUrl()
    {
        $this->expectException(EasyLeafletException::class);

        new Icon('');
    }
}
