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
            map.fitBounds(latLngs, config.view.maxZoom ? { maxZoom: config.view.maxZoom } : undefined);
        } else {
            map.setView([config.view.center.lat, config.view.center.lng], config.view.zoom);
        }
    }

    window.EasyLeaflet = { init: init };
})(window, L);
