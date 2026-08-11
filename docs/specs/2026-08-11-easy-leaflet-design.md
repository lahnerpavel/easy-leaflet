# easy-leaflet — Design

## Kontext a cíl

Firma (OLC/orbinet) má na desítkách webů kopírovaný modul `OrbinetLeaflet` (Alfréd a Hermína, Fortex Reality, Penzion Osika — vždy 4 stejně jmenované soubory, ručně upravované na každém webu zvlášť). Kopírování bez centrální verze vedlo ke skutečné regresi: jiné pořadí konstruktorových argumentů u `CustomPointStyle` mezi kopiemi a rozbitý Mapy.cz tile layer v jedné z nich (chybějící JS proměnné).

Cíl: jedna centrální Composer knihovna `forup/easy-leaflet`, kterou lze nasadit napříč projekty (od PHP 5.6 po nejnovější), s jednotným API, bez duplicity a bez pain pointů zjištěných v legacy kódu (viz níže).

Prvním reálným konzumentem je projekt **hydromenice**, který dnes používá Seznam Mapy.cz "SMap" JS API se 2 statickými markery (Kojetín, Brno) z DB entity `KontaktAdresar` (`GPS_LAT`, `GPS_LNG`, `NAZEV`, adresa, telefon, otevírací doba), s per-marker ikonou vybíranou natvrdo podle prvního písmene názvu města. Bez popupů — data se dnes duplikují do HTML karet vedle mapy.

## Zjištěné pain pointy legacy implementace (proč tento návrh vypadá, jak vypadá)

1. Žádný namespace/PSR-4, globální třídy, `require` bez `require_once` → riziko "Cannot redeclare class".
2. PHP použité jako template engine — mix `<?`, `<?php`, `<?=` v jedné metodě (`renderScript()`), netestovatelné.
3. Copy-paste bez verzování → tichá divergence (jiné pořadí argumentů, rozbitá varianta).
4. JS array skládaný ručním string-concatem (`echo $point->renderJson() . ', ';`) — fragilní, závislé na trailing čárce.
5. Dvojité JSON encodování (`iconStyle` jako JSON string uvnitř JSON objektu, klient dělá `JSON.parse` navíc).
6. Žádný escaping popup obsahu → stored XSS riziko, jakmile bude obsah z CMS/formuláře.
7. Hardcoded API klíče a GPS souřadnice přímo v šabloně.
8. `die()` při chybějící konfiguraci — zabije celý request místo vyhoditelné výjimky.
9. Tile provider výběr jako if/elseif chain — přidání providera = editace core třídy.
10. Hardcoded DOM id (`#mapLeaflet`) → nejde mít 2 mapy na stránce.

## Balíček

- Packagist: `forup/easy-leaflet`
- PHP namespace: `Forup\EasyLeaflet`
- `composer.json`: `"php": ">=5.6"`, PSR-4 autoloading
- Repo: `gitlab.olc.cz/forup/easy-leaflet` (tento repozitář)

## Architektura

Tři vrstvy, striktně oddělené — žádná vrstva nemixuje "spočítat data" a "vytisknout výstup":

```
Value objekty (data)  →  Map (builder)  →  Renderer (výstup)
LatLng, Icon, Popup,      fluentní API,      1x json_encode() celého
TileLayer, Marker         skládá config      payloadu + krátký inline
                                              <script> voláním sdíleného
                                              easy-leaflet.js
```

### Value objekty

- **`LatLng`** — `{lat, lng}`, immutable.
- **`Icon`** — mirror Leaflet `L.Icon` options (`iconUrl`, `iconSize`, `iconAnchor`, `shadowUrl`, `shadowSize`, `shadowAnchor`, `popupAnchor`). Convenience factory `Icon::centerBottom($iconUrl, $width, $height)` — generalizace legacy `ANCHOR_POSITION_CENTER_BOTTOM`, dopočítá anchor/popupAnchor z rozměrů ikony.
- **`Popup`** — `Popup::text($string)` (escapováno přes `htmlspecialchars` — výchozí a bezpečná varianta) nebo `Popup::html($trustedHtml)` (explicitní opt-out z escapingu, na odpovědnost volajícího). Volný text/HTML stačí — žádná vestavěná "adresní" šablona v v1.
- **`TileLayer`** — `{urlTemplate, attribution, options}` + registr hotových presetů jako statické factory metody: `TileLayer::openStreetMap()` (default), `TileLayer::cartoVoyager()`, `TileLayer::cartoLight()`, `TileLayer::cartoDark()`, `TileLayer::mapyCz($apiKey)`. Nahrazuje legacy if/elseif chain malým rozšiřitelným registrem — přidání dalšího presetu je nová statická metoda, ne úprava podmínek uvnitř core třídy. **V1 = jedna zvolená vrstva na mapu, žádný runtime přepínač vrstev pro návštěvníka** (`L.control.layers()` je vědomě mimo scope).
- **`Marker`** — `{LatLng, ?Icon, ?Popup}`.

### `Map` (fluentní builder)

```php
$map = Map::create()
    ->setTileLayer(TileLayer::openStreetMap())
    ->addMarker(new Marker($latLng, $icon, $popup))
    ->render();
```

- Styl blízký legacy `LeafletMap` (fluentní settery vracející `$this`), ale bez jakékoli tiskové logiky uvnitř — `render()` jen delegate na `Renderer`.
- **Container id**: pokud není explicitně zadán (`setContainerId(...)`), `Map` si vygeneruje unikátní id sám → řeší legacy bug s hardcoded `#mapLeaflet` a umožňuje víc map na stránce.
- **Auto-fit-bounds** (výchozí chování, lze vypnout): 2+ markery → `fitBounds` přes všechny markery; přesně 1 marker → `setView` na jeho souřadnice s nastavitelným výchozím zoomem; 0 markerů → buď musí být explicitně zadán `setCenter()+setZoom()`, jinak `render()` vyhodí `EasyLeafletException` (žádný `die()`, žádné tiché vykreslení rozbité mapy).

### `Renderer`

- Sesbírá celý config (tile layer, container id, markery s icon/popup, center/zoom/bounds) do jedné asociativní struktury.
- Provede **jeden** `json_encode()` celého payloadu.
- Vytiskne `<div id="{containerId}"></div><script>EasyLeaflet.init({json});</script>` — krátký, čistý inline skript. Odpovídá preferenci "co nejčistší výstup" a zároveň řeší pain point #4/#5 (žádné ruční skládání JS arrayů, žádné dvojité encodování).

### `easy-leaflet.js`

- Malý sdílený vanilla JS soubor dodaný knihovnou, obsahuje `window.EasyLeaflet.init(config)`, který dělá samotné `L.map(...)`, `L.tileLayer(...).addTo(map)`, `L.marker(...).addTo(map).bindPopup(...)`, `fitBounds`/`setView`.
- Nezávislý na PHP verzi (běžný moderní JS, žádný build step).
- Includuje se **jednou** na stránku (ne per-mapa).

### Volitelný `assets()` helper

- `EasyLeaflet::assets()->render()` (nebo podobně) vytiskne `<link>` pro `leaflet.css` a `<script>` pro `leaflet.js` + `easy-leaflet.js` — s možností zadat verzi/CDN URL nebo lokální cestu.
- Nepovinné — projekt může Leaflet a `easy-leaflet.js` includovat i sám, knihovna to nevynucuje.

## Chybové stavy

- Žádné `die()` nikde v knihovně.
- Chybějící povinná konfigurace → `Forup\EasyLeaflet\EasyLeafletException` (nebo podtřída) s jasnou zprávou, chytatelná v konzumentském kódu.

## PHP kompatibilita (reálně 5.6+)

Syntaxe omezená na to, co běhá na PHP 5.6:
- žádné scalar type-hinty parametrů (přišly v 7.0)
- žádné return type declarations (7.0)
- žádné `??` / `??=` (7.0 / 7.4) — použít `isset()`/ternary
- žádné arrow funkce (7.4), žádný `match` (8.0)
- žádné constructor property promotion, `readonly`, enums (8.0/8.1)
- `private $prop` + PHPDoc `@var`/`@param`/`@return`, fluentní settery vracející `$this`

### Testování / CI

- PHPUnit `^5.7` (běhá od PHP 5.6 nahoru) — jedna sada testů pro všechny verze, žádné rozdvojené test suity.
- GitLab CI matrix (`gitlab.olc.cz/orbinet`): PHP 5.6 / 7.4 / 8.1 / 8.3 (min. a nejnovější + mezistupně), stejné testy na každé verzi.
- `easy-leaflet.js` bez vlastního test frameworku v v1 (malý, jednoduchý, ověřuje se manuálně/vizuálně na reálné mapě).

## Rozsah v1

**Obsahuje:**
- Markery s custom ikonou (vč. `Icon::centerBottom` zkratky)
- Popupy s escapovaným textem výchozí volbou, opt-out pro trusted HTML
- Jeden zvolený tile layer z presetového registru (OSM, Carto Voyager/Light/Dark, Mapy.cz) — bez runtime přepínání
- Auto-fit-bounds s fallbackem na explicitní center/zoom
- Víc map na stránce (auto-generované container id)
- Volitelný `assets()` helper pro `<link>/<script>` tagy

**Záměrně NEobsahuje (YAGNI, architektura na to nechává místo, ale nestaví se to teď):**
- Clustering markerů
- Polygony / polyline / kruhy
- Runtime přepínač vrstev (`L.control.layers()`)
- Geocoding
- Framework adaptéry (Twig extension, Symfony bundle)

Tyto body se doplní až konkrétní projekt reálně potřebu prokáže.
