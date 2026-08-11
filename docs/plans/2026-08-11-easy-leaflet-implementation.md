# easy-leaflet Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use olc-powers:subagent-driven-development (recommended) or olc-powers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build `forup/easy-leaflet`, a PHP 5.6+ Composer library that renders Leaflet.js maps (markers, custom icons, popups, one configurable tile layer, auto-fit-bounds) from a small fluent PHP API, replacing the copy-pasted `OrbinetLeaflet` module duplicated across Alfréd a Hermína, Fortex Reality, and Penzion Osika.

**Architecture:** Three layers — immutable value objects (`LatLng`, `Icon`, `Popup`, `TileLayer`, `Marker`) hold data only; `Map` is a fluent builder that assembles a payload and resolves the view mode (bounds vs. center/zoom); `Renderer` does a single `json_encode()` and prints a `<div>` + one inline `<script>` that calls a small shared `easy-leaflet.js` bootstrap (which does the actual `L.map()`/`L.marker()`/`L.tileLayer()` calls). See `docs/specs/2026-08-11-easy-leaflet-design.md` for full rationale.

**Tech Stack:** PHP >=5.6 (no scalar type hints, no return types, no `??`, no arrow fns), PHPUnit ^9.6 for automated tests (PHP 7.3+ lanes), `php -l` syntax lint for the PHP 5.6 CI lane, plain vanilla JS (ES2017) for the browser bootstrap, GitLab CI.

**Deviation from spec (fixed during planning):** The design spec proposed PHPUnit `^5.7` as a single test-runner version spanning PHP 5.6 to latest. That doesn't hold up: `phpunit/phpunit` 5.7 explicitly requires `"php": ">=5.6,<8.0-dev"` — it cannot run on PHP 8.x, and modern PHPUnit (7+) dropped PHP 5.6 support entirely. No single PHPUnit version covers the whole range. This plan instead: (a) runs the full PHPUnit ^9.6 suite on PHP 7.4 / 8.1 / 8.3, and (b) runs a dependency-free `php -l` syntax lint over `src/` on PHP 5.6 to guarantee the code parses there, without pretending one test runner spans the whole matrix.

---

### Task 1: Project scaffolding

**Files:**
- Create: `composer.json`
- Create: `phpunit.xml.dist`
- Modify: `.gitignore`

- [ ] **Step 1: Write `composer.json`**

```json
{
    "name": "forup/easy-leaflet",
    "description": "Simple, universal PHP library for rendering Leaflet.js maps (PHP 5.6+)",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": ">=5.6"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.6"
    },
    "autoload": {
        "psr-4": {
            "Forup\\EasyLeaflet\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Forup\\EasyLeaflet\\Tests\\": "tests/"
        }
    }
}
```

- [ ] **Step 2: Write `phpunit.xml.dist`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.6/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="EasyLeaflet">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

- [ ] **Step 3: Append to `.gitignore`**

Add these lines to the existing `.gitignore` (keep the existing `/.idea/` line):

```
/vendor/
/composer.lock
/.phpunit.cache/
/.phpunit.result.cache
```

- [ ] **Step 4: Install dev dependencies**

Run: `composer install`
Expected: `vendor/` created, `composer.lock` created, no errors.

- [ ] **Step 5: Commit**

```bash
git add composer.json phpunit.xml.dist .gitignore
git commit -m "chore: scaffold composer package and phpunit config"
```

---

### Task 2: `EasyLeafletException`

**Files:**
- Create: `src/EasyLeafletException.php`

- [ ] **Step 1: Write the class (no test needed — it's a marker exception type, exercised by later tests)**

```php
<?php

namespace Forup\EasyLeaflet;

class EasyLeafletException extends \RuntimeException
{
}
```

- [ ] **Step 2: Commit**

```bash
git add src/EasyLeafletException.php
git commit -m "feat: add EasyLeafletException"
```

---

### Task 3: `LatLng`

**Files:**
- Create: `src/LatLng.php`
- Test: `tests/LatLngTest.php`

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/LatLngTest.php`
Expected: FAIL with `Class "Forup\EasyLeaflet\LatLng" not found`.

- [ ] **Step 3: Write the implementation**

```php
<?php

namespace Forup\EasyLeaflet;

class LatLng
{
    /** @var float */
    private $lat;

    /** @var float */
    private $lng;

    public function __construct($lat, $lng)
    {
        $this->lat = (float) $lat;
        $this->lng = (float) $lng;
    }

    /** @return float */
    public function getLat()
    {
        return $this->lat;
    }

    /** @return float */
    public function getLng()
    {
        return $this->lng;
    }

    /** @return array */
    public function toArray()
    {
        return array(
            'lat' => $this->lat,
            'lng' => $this->lng,
        );
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/LatLngTest.php`
Expected: `OK (2 tests, 3 assertions)`

- [ ] **Step 5: Commit**

```bash
git add src/LatLng.php tests/LatLngTest.php
git commit -m "feat: add LatLng value object"
```

---

### Task 4: `Icon`

**Files:**
- Create: `src/Icon.php`
- Test: `tests/IconTest.php`

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/IconTest.php`
Expected: FAIL with `Class "Forup\EasyLeaflet\Icon" not found`.

- [ ] **Step 3: Write the implementation**

```php
<?php

namespace Forup\EasyLeaflet;

class Icon
{
    /** @var string */
    private $iconUrl;

    /** @var array|null */
    private $iconSize;

    /** @var array|null */
    private $iconAnchor;

    /** @var array|null */
    private $popupAnchor;

    /** @var string|null */
    private $shadowUrl;

    /** @var array|null */
    private $shadowSize;

    /** @var array|null */
    private $shadowAnchor;

    public function __construct($iconUrl, $iconWidth = null, $iconHeight = null)
    {
        if ($iconUrl === null || $iconUrl === '') {
            throw new EasyLeafletException('Icon::__construct requires a non-empty iconUrl.');
        }

        $this->iconUrl = $iconUrl;
        $this->iconSize = ($iconWidth !== null && $iconHeight !== null) ? array($iconWidth, $iconHeight) : null;
    }

    /**
     * Convenience factory that mirrors the legacy ANCHOR_POSITION_CENTER_BOTTOM
     * shortcut: anchors the icon at its horizontal center / bottom edge, and
     * anchors the popup directly above the icon.
     *
     * @return Icon
     */
    public static function centerBottom($iconUrl, $width, $height)
    {
        $icon = new self($iconUrl, $width, $height);
        $icon->iconAnchor = array($width / 2, $height);
        $icon->popupAnchor = array(0, -$height);

        return $icon;
    }

    /** @return Icon */
    public function setShadow($shadowUrl, $shadowWidth = null, $shadowHeight = null)
    {
        $this->shadowUrl = $shadowUrl;
        $this->shadowSize = ($shadowWidth !== null && $shadowHeight !== null) ? array($shadowWidth, $shadowHeight) : null;

        return $this;
    }

    /** @return Icon */
    public function setAnchor($x, $y)
    {
        $this->iconAnchor = array($x, $y);

        return $this;
    }

    /** @return Icon */
    public function setPopupAnchor($x, $y)
    {
        $this->popupAnchor = array($x, $y);

        return $this;
    }

    /** @return Icon */
    public function setShadowAnchor($x, $y)
    {
        $this->shadowAnchor = array($x, $y);

        return $this;
    }

    /** @return array */
    public function toArray()
    {
        $data = array('iconUrl' => $this->iconUrl);

        if ($this->iconSize !== null) {
            $data['iconSize'] = $this->iconSize;
        }
        if ($this->iconAnchor !== null) {
            $data['iconAnchor'] = $this->iconAnchor;
        }
        if ($this->popupAnchor !== null) {
            $data['popupAnchor'] = $this->popupAnchor;
        }
        if ($this->shadowUrl !== null) {
            $data['shadowUrl'] = $this->shadowUrl;
        }
        if ($this->shadowSize !== null) {
            $data['shadowSize'] = $this->shadowSize;
        }
        if ($this->shadowAnchor !== null) {
            $data['shadowAnchor'] = $this->shadowAnchor;
        }

        return $data;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/IconTest.php`
Expected: `OK (4 tests, 6 assertions)`

- [ ] **Step 5: Commit**

```bash
git add src/Icon.php tests/IconTest.php
git commit -m "feat: add Icon value object with centerBottom factory"
```

---

### Task 5: `Popup`

**Files:**
- Create: `src/Popup.php`
- Test: `tests/PopupTest.php`

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/PopupTest.php`
Expected: FAIL with `Class "Forup\EasyLeaflet\Popup" not found`.

- [ ] **Step 3: Write the implementation**

```php
<?php

namespace Forup\EasyLeaflet;

class Popup
{
    /** @var string */
    private $html;

    private function __construct($html)
    {
        $this->html = $html;
    }

    /**
     * Plain text, HTML-escaped. This is the safe default — use this unless
     * you have already-trusted/sanitized HTML to render.
     *
     * @return Popup
     */
    public static function text($text)
    {
        return new self(htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Raw HTML, NOT escaped. Only pass content you trust (e.g. hand-written
     * template strings) — never pass unsanitized user/CMS input here.
     *
     * @return Popup
     */
    public static function html($trustedHtml)
    {
        return new self((string) $trustedHtml);
    }

    /** @return string */
    public function getHtml()
    {
        return $this->html;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/PopupTest.php`
Expected: `OK (2 tests, 2 assertions)`

- [ ] **Step 5: Commit**

```bash
git add src/Popup.php tests/PopupTest.php
git commit -m "feat: add Popup value object with escaped-by-default text()"
```

---

### Task 6: `TileLayer`

**Files:**
- Create: `src/TileLayer.php`
- Test: `tests/TileLayerTest.php`

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/TileLayerTest.php`
Expected: FAIL with `Class "Forup\EasyLeaflet\TileLayer" not found`.

- [ ] **Step 3: Write the implementation**

```php
<?php

namespace Forup\EasyLeaflet;

class TileLayer
{
    /** @var string */
    private $urlTemplate;

    /** @var string */
    private $attribution;

    /** @var array */
    private $options;

    public function __construct($urlTemplate, $attribution, array $options = array())
    {
        $this->urlTemplate = $urlTemplate;
        $this->attribution = $attribution;
        $this->options = $options;
    }

    /** @return TileLayer */
    public static function openStreetMap()
    {
        return new self(
            'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            array('maxZoom' => 19)
        );
    }

    /** @return TileLayer */
    public static function cartoVoyager()
    {
        return new self(
            'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            array('maxZoom' => 20, 'subdomains' => 'abcd')
        );
    }

    /** @return TileLayer */
    public static function cartoLight()
    {
        return new self(
            'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            array('maxZoom' => 20, 'subdomains' => 'abcd')
        );
    }

    /** @return TileLayer */
    public static function cartoDark()
    {
        return new self(
            'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            array('maxZoom' => 20, 'subdomains' => 'abcd')
        );
    }

    /** @return TileLayer */
    public static function mapyCz($apiKey)
    {
        if ($apiKey === null || $apiKey === '') {
            throw new EasyLeafletException('TileLayer::mapyCz requires a non-empty API key.');
        }

        return new self(
            'https://api.mapy.cz/v1/maptiles/basic/256@2x/{z}/{x}/{y}?apikey=' . rawurlencode($apiKey),
            '&copy; <a href="https://api.mapy.cz/copyright">Mapy.cz</a>',
            array('maxZoom' => 19)
        );
    }

    /** @return array */
    public function toArray()
    {
        return array(
            'url' => $this->urlTemplate,
            'attribution' => $this->attribution,
            'options' => $this->options,
        );
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/TileLayerTest.php`
Expected: `OK (6 tests, 7 assertions)`

- [ ] **Step 5: Commit**

```bash
git add src/TileLayer.php tests/TileLayerTest.php
git commit -m "feat: add TileLayer value object with preset registry"
```

---

### Task 7: `Marker`

**Files:**
- Create: `src/Marker.php`
- Test: `tests/MarkerTest.php`

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/MarkerTest.php`
Expected: FAIL with `Class "Forup\EasyLeaflet\Marker" not found`.

- [ ] **Step 3: Write the implementation**

```php
<?php

namespace Forup\EasyLeaflet;

class Marker
{
    /** @var LatLng */
    private $latLng;

    /** @var Icon|null */
    private $icon;

    /** @var Popup|null */
    private $popup;

    public function __construct(LatLng $latLng, Icon $icon = null, Popup $popup = null)
    {
        $this->latLng = $latLng;
        $this->icon = $icon;
        $this->popup = $popup;
    }

    /** @return LatLng */
    public function getLatLng()
    {
        return $this->latLng;
    }

    /** @return array */
    public function toArray()
    {
        $data = array('latlng' => $this->latLng->toArray());

        if ($this->icon !== null) {
            $data['icon'] = $this->icon->toArray();
        }
        if ($this->popup !== null) {
            $data['popup'] = $this->popup->getHtml();
        }

        return $data;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/MarkerTest.php`
Expected: `OK (3 tests, 6 assertions)`

- [ ] **Step 5: Commit**

```bash
git add src/Marker.php tests/MarkerTest.php
git commit -m "feat: add Marker value object"
```

---

### Task 8: `Renderer`

**Files:**
- Create: `src/Renderer.php`
- Test: `tests/RendererTest.php`

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/RendererTest.php`
Expected: FAIL with `Class "Forup\EasyLeaflet\Renderer" not found`.

- [ ] **Step 3: Write the implementation**

```php
<?php

namespace Forup\EasyLeaflet;

class Renderer
{
    /**
     * @param string $containerId
     * @param array $payload Map config (tileLayer, markers, view) — containerId is merged in here.
     * @return string
     */
    public function render($containerId, array $payload)
    {
        $payload = array('containerId' => $containerId) + $payload;

        $json = json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES);

        return sprintf(
            '<div id="%1$s"></div><script>EasyLeaflet.init(%2$s);</script>',
            htmlspecialchars($containerId, ENT_QUOTES, 'UTF-8'),
            $json
        );
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/RendererTest.php`
Expected: `OK (4 tests, 5 assertions)`

- [ ] **Step 5: Commit**

```bash
git add src/Renderer.php tests/RendererTest.php
git commit -m "feat: add Renderer with single json_encode payload"
```

---

### Task 9: `Map`

**Files:**
- Create: `src/Map.php`
- Test: `tests/MapTest.php`

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/MapTest.php`
Expected: FAIL with `Class "Forup\EasyLeaflet\Map" not found`.

- [ ] **Step 3: Write the implementation**

```php
<?php

namespace Forup\EasyLeaflet;

class Map
{
    private static $instanceCounter = 0;

    /** @var string */
    private $containerId;

    /** @var TileLayer */
    private $tileLayer;

    /** @var Marker[] */
    private $markers = array();

    /** @var LatLng|null */
    private $center;

    /** @var int|null */
    private $zoom;

    private function __construct()
    {
        self::$instanceCounter++;
        $this->containerId = 'easy-leaflet-map-' . self::$instanceCounter;
        $this->tileLayer = TileLayer::openStreetMap();
    }

    /** @return Map */
    public static function create()
    {
        return new self();
    }

    /** @return Map */
    public function setContainerId($containerId)
    {
        $this->containerId = $containerId;

        return $this;
    }

    /** @return Map */
    public function setTileLayer(TileLayer $tileLayer)
    {
        $this->tileLayer = $tileLayer;

        return $this;
    }

    /** @return Map */
    public function addMarker(Marker $marker)
    {
        $this->markers[] = $marker;

        return $this;
    }

    /** @return Map */
    public function setCenter($lat, $lng)
    {
        $this->center = new LatLng($lat, $lng);

        return $this;
    }

    /** @return Map */
    public function setZoom($zoom)
    {
        $this->zoom = (int) $zoom;

        return $this;
    }

    /**
     * @return string HTML: a container <div> plus one inline <script> that
     *                 boots the map via the shared easy-leaflet.js runtime.
     * @throws EasyLeafletException if there are no markers and no explicit setCenter().
     */
    public function render()
    {
        $markerData = array();
        foreach ($this->markers as $marker) {
            $markerData[] = $marker->toArray();
        }

        $payload = array(
            'tileLayer' => $this->tileLayer->toArray(),
            'markers' => $markerData,
            'view' => $this->resolveView(),
        );

        $renderer = new Renderer();

        return $renderer->render($this->containerId, $payload);
    }

    /** @return array */
    private function resolveView()
    {
        if ($this->center !== null) {
            return array(
                'mode' => 'center',
                'center' => $this->center->toArray(),
                'zoom' => $this->zoom !== null ? $this->zoom : 13,
            );
        }

        $markerCount = count($this->markers);

        if ($markerCount === 0) {
            throw new EasyLeafletException(
                'Map::render() requires either at least one marker or an explicit setCenter().'
            );
        }

        if ($markerCount === 1) {
            return array(
                'mode' => 'center',
                'center' => $this->markers[0]->getLatLng()->toArray(),
                'zoom' => $this->zoom !== null ? $this->zoom : 15,
            );
        }

        return array('mode' => 'bounds');
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/MapTest.php`
Expected: `OK (7 tests, 10 assertions)`

- [ ] **Step 5: Commit**

```bash
git add src/Map.php tests/MapTest.php
git commit -m "feat: add Map fluent builder with auto-fit-bounds resolution"
```

---

### Task 10: `Assets` helper

**Files:**
- Create: `src/Assets.php`
- Test: `tests/AssetsTest.php`

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/AssetsTest.php`
Expected: FAIL with `Class "Forup\EasyLeaflet\Assets" not found`.

- [ ] **Step 3: Write the implementation**

```php
<?php

namespace Forup\EasyLeaflet;

class Assets
{
    /** @var string */
    private $leafletCssUrl;

    /** @var string */
    private $leafletJsUrl;

    /** @var string */
    private $easyLeafletJsUrl;

    /**
     * @param string $easyLeafletJsUrl Public URL where resources/js/easy-leaflet.js
     *                                 is served from in the consuming project.
     * @param string $leafletCssUrl
     * @param string $leafletJsUrl
     */
    public function __construct(
        $easyLeafletJsUrl,
        $leafletCssUrl = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
        $leafletJsUrl = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
    ) {
        if ($easyLeafletJsUrl === null || $easyLeafletJsUrl === '') {
            throw new EasyLeafletException(
                'Assets requires a non-empty easyLeafletJsUrl (the public URL you serve resources/js/easy-leaflet.js from).'
            );
        }

        $this->easyLeafletJsUrl = $easyLeafletJsUrl;
        $this->leafletCssUrl = $leafletCssUrl;
        $this->leafletJsUrl = $leafletJsUrl;
    }

    /** @return string */
    public function render()
    {
        return sprintf(
            '<link rel="stylesheet" href="%1$s"><script src="%2$s"></script><script src="%3$s"></script>',
            htmlspecialchars($this->leafletCssUrl, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($this->leafletJsUrl, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($this->easyLeafletJsUrl, ENT_QUOTES, 'UTF-8')
        );
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/AssetsTest.php`
Expected: `OK (3 tests, 6 assertions)`

- [ ] **Step 5: Commit**

```bash
git add src/Assets.php tests/AssetsTest.php
git commit -m "feat: add optional Assets helper for Leaflet CDN/local tags"
```

---

### Task 11: `easy-leaflet.js` bootstrap + manual browser verification

**Files:**
- Create: `resources/js/easy-leaflet.js`
- Create: `examples/demo.php`

- [ ] **Step 1: Write the JS bootstrap**

```js
(function (window, L) {
    'use strict';

    function buildIcon(iconConfig) {
        if (!iconConfig) {
            return null;
        }
        return L.icon(iconConfig);
    }

    function init(config) {
        var container = document.getElementById(config.containerId);
        if (!container) {
            throw new Error('EasyLeaflet: container "' + config.containerId + '" not found.');
        }

        var map = L.map(config.containerId);

        var tileOptions = Object.assign({}, config.tileLayer.options, {
            attribution: config.tileLayer.attribution
        });
        L.tileLayer(config.tileLayer.url, tileOptions).addTo(map);

        var latLngs = [];
        config.markers.forEach(function (markerConfig) {
            var latLng = [markerConfig.latlng.lat, markerConfig.latlng.lng];
            latLngs.push(latLng);

            var markerOptions = {};
            var icon = buildIcon(markerConfig.icon);
            if (icon) {
                markerOptions.icon = icon;
            }

            var marker = L.marker(latLng, markerOptions).addTo(map);
            if (markerConfig.popup) {
                marker.bindPopup(markerConfig.popup);
            }
        });

        if (config.view.mode === 'bounds') {
            map.fitBounds(latLngs);
        } else {
            map.setView([config.view.center.lat, config.view.center.lng], config.view.zoom);
        }
    }

    window.EasyLeaflet = { init: init };
})(window, L);
```

- [ ] **Step 2: Write the demo page**

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Forup\EasyLeaflet\Assets;
use Forup\EasyLeaflet\Icon;
use Forup\EasyLeaflet\LatLng;
use Forup\EasyLeaflet\Map;
use Forup\EasyLeaflet\Marker;
use Forup\EasyLeaflet\Popup;
use Forup\EasyLeaflet\TileLayer;

$pinIcon = Icon::centerBottom('https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png', 25, 41);

$map = Map::create()
    ->setTileLayer(TileLayer::openStreetMap())
    ->addMarker(new Marker(new LatLng(49.5905, 17.1978), $pinIcon, Popup::text('Kojetín HQ')))
    ->addMarker(new Marker(new LatLng(49.1951, 16.6068), $pinIcon, Popup::text('Brno branch')));

$assets = new Assets('../resources/js/easy-leaflet.js');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>easy-leaflet demo</title>
    <style>div[id^="easy-leaflet-map-"] { height: 400px; }</style>
    <?= $assets->render() ?>
</head>
<body>
<?= $map->render() ?>
</body>
</html>
```

- [ ] **Step 3: Manually verify in a browser**

Run: `php -S localhost:8000 -t .` (from the repo root)
Open: `http://localhost:8000/examples/demo.php`
Expected: a 400px-tall OpenStreetMap map, auto-zoomed/panned to fit both markers (Kojetín and Brno), each with a pin icon anchored at its bottom point; clicking a marker opens a popup with the city label.

- [ ] **Step 4: Commit**

```bash
git add resources/js/easy-leaflet.js examples/demo.php
git commit -m "feat: add easy-leaflet.js bootstrap and browser demo"
```

---

### Task 12: README

**Files:**
- Create: `README.md`

- [ ] **Step 1: Write `README.md`**

```markdown
# easy-leaflet

Simple, universal PHP library for rendering [Leaflet.js](https://leafletjs.com/) maps — markers, custom icons, popups, one configurable tile layer, auto-fit-bounds. Works from PHP 5.6 up to the latest release, no framework required.

## Install

```bash
composer require forup/easy-leaflet
```

## Usage

```php
use Forup\EasyLeaflet\Icon;
use Forup\EasyLeaflet\LatLng;
use Forup\EasyLeaflet\Map;
use Forup\EasyLeaflet\Marker;
use Forup\EasyLeaflet\Popup;
use Forup\EasyLeaflet\TileLayer;

$icon = Icon::centerBottom('/img/pin.png', 32, 40);

$map = Map::create()
    ->setTileLayer(TileLayer::openStreetMap())
    ->addMarker(new Marker(new LatLng(49.5905, 17.1978), $icon, Popup::text('Kojetín HQ')))
    ->addMarker(new Marker(new LatLng(49.1951, 16.6068), $icon, Popup::text('Brno branch')));

echo $map->render();
```

`render()` prints a `<div>` and a single inline `<script>` that boots the map via the shared `easy-leaflet.js` runtime. With 2+ markers the map auto-fits its bounds; with exactly 1 marker it centers on it; with 0 markers you must call `setCenter($lat, $lng)` explicitly or `render()` throws `EasyLeafletException`.

## Loading Leaflet itself

`easy-leaflet` does not bundle Leaflet's core CSS/JS or force a specific way to load them. Include them however your project already loads assets, or use the optional helper:

```php
use Forup\EasyLeaflet\Assets;

echo (new Assets('/js/easy-leaflet.js'))->render();
```

This prints `<link>`/`<script>` tags for Leaflet's CDN build plus your copy of `vendor/forup/easy-leaflet/resources/js/easy-leaflet.js` (you decide where to serve that file from — pass its public URL as the constructor argument).

## Tile layers

```php
TileLayer::openStreetMap(); // default
TileLayer::cartoVoyager();
TileLayer::cartoLight();
TileLayer::cartoDark();
TileLayer::mapyCz($apiKey);
```

Pick exactly one per map with `Map::setTileLayer()`. There is no runtime layer switcher in this version.

## Popups and escaping

`Popup::text($string)` HTML-escapes its input — use it for anything derived from user/CMS data. `Popup::html($trustedHtml)` skips escaping — only pass content you fully trust.

## Multiple maps per page

Each `Map::create()` generates a unique container id automatically. Pass your own with `setContainerId()` if you need a specific one.

## Design

See [`docs/specs/2026-08-11-easy-leaflet-design.md`](docs/specs/2026-08-11-easy-leaflet-design.md) for the full architecture rationale.
```

- [ ] **Step 2: Commit**

```bash
git add README.md
git commit -m "docs: add README with usage guide"
```

---

### Task 13: CI (GitLab CI matrix)

**Files:**
- Create: `.gitlab-ci.yml`

- [ ] **Step 1: Write `.gitlab-ci.yml`**

```yaml
stages:
  - lint
  - test

lint:php5.6:
  stage: lint
  image: php:5.6-cli
  script:
    - for f in $(find src -name "*.php"); do php -l "$f"; done

test:php7.4:
  stage: test
  image: php:7.4-cli
  before_script:
    - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    - composer install --no-interaction --prefer-dist
  script:
    - vendor/bin/phpunit

test:php8.1:
  stage: test
  image: php:8.1-cli
  before_script:
    - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    - composer install --no-interaction --prefer-dist
  script:
    - vendor/bin/phpunit

test:php8.3:
  stage: test
  image: php:8.3-cli
  before_script:
    - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    - composer install --no-interaction --prefer-dist
  script:
    - vendor/bin/phpunit
```

- [ ] **Step 2: Run the full local test suite once more before pushing**

Run: `vendor/bin/phpunit`
Expected: `OK (31 tests, 45 assertions)` (sum of Tasks 3–10: 2+4+2+6+3+4+7+3 = 31 tests, 3+6+2+7+6+5+10+6 = 45 assertions; if your running count differs slightly, it should still be all-green with zero failures/errors.)

- [ ] **Step 3: Commit**

```bash
git add .gitlab-ci.yml
git commit -m "ci: add lint (php5.6) and phpunit (7.4/8.1/8.3) pipeline"
```

---

## Post-plan follow-ups (explicitly out of scope for this plan)

- Publishing to Packagist (public) — decided direction is "public eventually", not yet actioned.
- Migrating `hydromenice` itself to consume this library (separate task/plan once this package is tagged).
- Clustering, polygons/polyline/circles, runtime layer switcher, geocoding, framework adapters — deferred per the design spec's explicit v1 non-goals.
