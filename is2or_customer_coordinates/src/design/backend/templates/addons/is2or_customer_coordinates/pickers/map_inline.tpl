{$inline_initial_lat = $latitude|doubleval}
{if !$inline_initial_lat}
    {$inline_initial_lat = $smarty.const.IS2OR_CUSTOMER_COORDINATES_DEFAULT_LATITUDE|doubleval}
{/if}

{$inline_initial_lng = $longitude|doubleval}
{if !$inline_initial_lng}
    {$inline_initial_lng = $smarty.const.IS2OR_CUSTOMER_COORDINATES_DEFAULT_LONGITUDE|doubleval}
{/if}

<style>
.is2or-inline-map-wrap {
    position: relative;
    width: 100%;
    height: 300px;
    border: 1px solid #ccc;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 6px;
}
.is2or-inline-map-wrap .cm-geo-map-container {
    width: 100%;
    height: 100%;
}
.is2or-inline-map-overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    z-index: 999;
    cursor: default;
    background: transparent;
}
</style>

<div class="is2or-inline-map-wrap" id="is2or_inline_wrap_{$id}">
    <div class="cm-geo-map-container map-canvas"
        id="is2or_inline_map_{$id}"
        style="width:100%; height:100%;"
        data-ca-geo-map-initial-lat="{$inline_initial_lat}"
        data-ca-geo-map-initial-lng="{$inline_initial_lng}"
        data-ca-geo-map-language="{$smarty.const.CART_LANGUAGE}"
        data-ca-geo-map-marker-selector=".is2or-inline-marker__{$id}"
        data-ca-geo-map-controls-enable-zoom="true"
        data-ca-geo-map-controls-enable-fullscreen="false"
        data-ca-geo-map-controls-enable-layers="false"
        data-ca-geo-map-controls-enable-ruler="false"
        data-ca-geo-map-behaviors-enable-drag="false"
        data-ca-geo-map-behaviors-enable-drag-on-mobile="false"
        data-ca-geo-map-behaviors-enable-dbl-click-zoom="false"
        data-ca-geo-map-behaviors-enable-multi-touch="false"
    ></div>
    <div class="is2or-inline-map-overlay"></div>

    {if $latitude && $longitude}
        <div class="is2or-inline-marker__{$id} hidden"
            data-ca-geo-map-marker-lat="{$latitude}"
            data-ca-geo-map-marker-lng="{$longitude}"
        ></div>
    {/if}
</div>

<script>
(function (_, $) {ldelim}
    $(_.doc).ready(function () {ldelim}
        var fieldId = '{$id}';
        var $latInput     = $('#elm_latitude_'  + fieldId);
        var $lngInput     = $('#elm_longitude_' + fieldId);
        var $mapContainer = $('#is2or_inline_map_' + fieldId);

        function reloadMap(lat, lng) {ldelim}
            lat = parseFloat(lat);
            lng = parseFloat(lng);
            if (isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) {ldelim}
                return;
            {rdelim}
            if ($mapContainer.data('ceGeoMapInitialized')) {ldelim}
                $mapContainer.ceGeoMap('removeAllMarkers');
                $mapContainer.ceGeoMap('addMarkers', [{ldelim} lat: lat, lng: lng {rdelim}]);
                $mapContainer.ceGeoMap('setCenter', lat, lng);
            {rdelim} else {ldelim}
                $mapContainer.attr('data-ca-geo-map-initial-lat', lat);
                $mapContainer.attr('data-ca-geo-map-initial-lng', lng);
            {rdelim}
        {rdelim}

        function observeInput($input, callback) {ldelim}
            var observer = new MutationObserver(function (mutations) {ldelim}
                mutations.forEach(function (m) {ldelim}
                    if (m.type === 'attributes' && m.attributeName === 'value') {ldelim}
                        callback();
                    {rdelim}
                {rdelim});
            {rdelim});
            if ($input.length) {ldelim}
                observer.observe($input[0], {ldelim} attributes: true, attributeFilter: ['value'] {rdelim});
            {rdelim}
            var lastVal = $input.val();
            setInterval(function () {ldelim}
                var cur = $input.val();
                if (cur !== lastVal) {ldelim}
                    lastVal = cur;
                    callback();
                {rdelim}
            {rdelim}, 800);
        {rdelim}

        function onCoordChange() {ldelim}
            reloadMap($latInput.val(), $lngInput.val());
        {rdelim}

        if ($latInput.length && $lngInput.length) {ldelim}
            observeInput($latInput, onCoordChange);
            observeInput($lngInput, onCoordChange);
        {rdelim}
    {rdelim});
{rdelim})(Tygh, Tygh.$);
</script>
