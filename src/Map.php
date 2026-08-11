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
