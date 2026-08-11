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
