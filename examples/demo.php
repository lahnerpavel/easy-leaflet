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
