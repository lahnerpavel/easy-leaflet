<?php

namespace Forup\EasyLeaflet\Tests;

use Forup\EasyLeaflet\Renderer;
use PHPUnit\Framework\TestCase;

class RendererTest extends TestCase
{
    public function testRendersContainerDivWithGivenId()
    {
        $renderer = new Renderer();
        $html = $renderer->render('map-1', array('markers' => array()));

        $this->assertStringContainsString('<div id="map-1"></div>', $html);
    }

    public function testRendersSingleInlineScriptCallingEasyLeafletInit()
    {
        $renderer = new Renderer();
        $html = $renderer->render('map-1', array('markers' => array()));

        $this->assertStringContainsString('<script>EasyLeaflet.init(', $html);
        $this->assertSame(1, substr_count($html, '<script>'));
    }

    public function testPayloadIncludesContainerId()
    {
        $renderer = new Renderer();
        $html = $renderer->render('map-1', array('markers' => array()));

        $this->assertStringContainsString('"containerId":"map-1"', $html);
    }

    public function testEscapesContainerIdInHtmlAttribute()
    {
        $renderer = new Renderer();
        $html = $renderer->render('map-1"><script>alert(1)</script>', array('markers' => array()));

        $this->assertStringNotContainsString('"><script>alert(1)</script>', $html);
    }
}
