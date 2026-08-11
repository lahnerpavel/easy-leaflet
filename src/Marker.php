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
