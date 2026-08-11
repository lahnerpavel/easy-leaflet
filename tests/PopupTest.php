<?php

namespace Forup\EasyLeaflet\Tests;

use Forup\EasyLeaflet\Popup;
use PHPUnit\Framework\TestCase;

class PopupTest extends TestCase
{
    public function testTextEscapesHtmlByDefault()
    {
        $popup = Popup::text('<b>Hi</b> & "quote"');

        $this->assertSame('&lt;b&gt;Hi&lt;/b&gt; &amp; &quot;quote&quot;', $popup->getHtml());
    }

    public function testHtmlPassesThroughUnescaped()
    {
        $popup = Popup::html('<b>Trusted</b>');

        $this->assertSame('<b>Trusted</b>', $popup->getHtml());
    }
}
