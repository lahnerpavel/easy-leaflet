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
