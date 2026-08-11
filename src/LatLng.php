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
