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
