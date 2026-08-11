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
