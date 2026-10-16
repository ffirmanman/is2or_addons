<div class="customer-map-selector" title="{__("select_coordinates")}" id="map_picker_{$id}" style="height: 100%;">
    {$initial_latitude = $latitude|doubleval}
    {if !$initial_latitude}
        {$initial_latitude = $smarty.const.IS2OR_CUSTOMER_COORDINATES_DEFAULT_LATITUDE|doubleval}
    {/if}

    {$initial_longitude = $longitude|doubleval}
    {if !$initial_longitude}
        {$initial_longitude = $smarty.const.IS2OR_CUSTOMER_COORDINATES_DEFAULT_LONGITUDE|doubleval}
    {/if}

    <div class="cm-geo-map-container map-canvas" id="map_picker_container_{$id}" style="height: 100%; min-height: 350px;"
        data-ca-geo-map-initial-lat="{$initial_latitude}"
        data-ca-geo-map-initial-lng="{$initial_longitude}"
        data-ca-geo-map-language="{$smarty.const.CART_LANGUAGE}"
        data-ca-geo-map-marker-selector=".cm-customer-coordinates-map-marker__{$id}"
        data-ca-geo-map-controls-enable-zoom="true"
        data-ca-geo-map-controls-enable-fullscreen="true"
        data-ca-geo-map-controls-enable-layers="true"
        data-ca-geo-map-controls-enable-ruler="true"
        data-ca-geo-map-behaviors-enable-drag="true"
        data-ca-geo-map-behaviors-enable-drag-on-mobile="true"
        data-ca-geo-map-behaviors-enable-dbl-click-zoom="true"
        data-ca-geo-map-behaviors-enable-multi-touch="true"
    ></div>

    <div class="buttons-container hidden">
        <a class="cm-dialog-closer cm-cancel tool-link ty-btn">{__("cancel")}</a>
        <div class="ty-float-right">
            {include file="buttons/button.tpl" but_text=__("set") but_role="action" but_meta="ty-btn__primary cm-dialog-closer cm-map-save-location"}
        </div>
    </div>

    {if $latitude && $longitude}
        <div class="cm-customer-coordinates-map-marker__{$id} hidden"
            data-ca-geo-map-marker-lat="{$latitude}"
            data-ca-geo-map-marker-lng="{$longitude}"
        ></div>
    {/if}
</div>

<script>
(function (_, $) {
  $(_.doc).ready(function () {
    var $container = $('#map_picker_container_{$id}');
    
    // Fungsi sinkronisasi instan: Langsung memasukkan koordinat baru ke input value
    function fn_save_latest_coordinates(coords) {
      $('#elm_latitude_{$id}').val(coords.lat).trigger('change');
      $('#elm_longitude_{$id}').val(coords.lng).trigger('change');
    }
    
    $container.on('ce:geomap:click', function (e, data) {
      if (!data.lat || !data.lng) {
        return;
      }
      var $container = $(e.target);
      $container.ceGeoMap('removeAllMarkers');
      $container.ceGeoMap('addMarkers', [ data ]);
      fn_save_latest_coordinates(data);
    });
    
    $container.on('ce:geomap:search_result_select', function (e, data) {
      if (!data.lat || !data.lng) {
        return;
      }
      var $container = $(e.target);
      $container.ceGeoMap('removeAllMarkers');
      $container.ceGeoMap('addMarkers', [ data ]);
      $container.ceGeoMap('setCenter', data.lat, data.lng);
      fn_save_latest_coordinates(data);
    });

    $('#elm_latitude_{$id}, #elm_longitude_{$id}').on('change', function () {
        var currentLat = parseFloat($('#elm_latitude_{$id}').val());
        var currentLng = parseFloat($('#elm_longitude_{$id}').val());

        if (!isNaN(currentLat) && !isNaN(currentLng) && currentLat !== 0 && currentLng !== 0) {
            $container.ceGeoMap('setCenter', currentLat, currentLng);
            $container.ceGeoMap('removeAllMarkers');
            $container.ceGeoMap('addMarkers', [ {
                lat: currentLat,
                lng: currentLng
            } ]);
        }
    });
  });
})(Tygh, Tygh.$);
</script>