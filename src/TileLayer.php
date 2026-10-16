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

    /**
     * @param string|null $apiKey
     * @return TileLayer
     */
    public static function cartoVoyager($apiKey = null)
    {
        return new self(
            'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png' . self::cartoKeyQuery($apiKey),
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            array('maxZoom' => 20, 'subdomains' => 'abcd')
        );
    }

    /**
     * @param string|null $apiKey
     * @return TileLayer
     */
    public static function cartoLight($apiKey = null)
    {
        return new self(
            'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png' . self::cartoKeyQuery($apiKey),
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            array('maxZoom' => 20, 'subdomains' => 'abcd')
        );
    }

    /**
     * @param string|null $apiKey
     * @return TileLayer
     */
    public static function cartoDark($apiKey = null)
    {
        return new self(
            'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png' . self::cartoKeyQuery($apiKey),
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

    /**
     * @param string|null $apiKey
     * @return string
     */
    private static function cartoKeyQuery($apiKey)
    {
        return $apiKey === null || $apiKey === '' ? '' : '?key=' . rawurlencode($apiKey);
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
