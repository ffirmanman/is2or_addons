(function(_, $) {
  $(_.doc).on('click', '.is2or-spl-integration-generate-address', function() {
    const $btn = $(this);
    const $type = $btn.data('ca-element-type');
    const $targetId = $btn.data('ca-element-id');
    const $target = $('#' + $targetId);
    const $targetValue = $target.val();

    if ($targetValue) {
      $.ceAjax('request', fn_url('is2or_spl_integration.generate_address'), {
        method: 'post',
        data: {
          short_address: $targetValue
        },
        callback: function(response) {
          if (response.success) {
            const data = response.data[0];
            const isShipping = ($type == 'S');
            const isVendor = ($type == 'C');
            const isAdminArea = $('body').hasClass('is-backend');

            if (isVendor) {
              // Vendor/Company form field mapping
              _is2orFillVendorAddress(data);
            } else if (isAdminArea) {
              // Admin editing customer profile
              _is2orFillAdminCustomerAddress(data, isShipping);
            } else {
              // Customer profile field mapping (frontend)
              _is2orFillCustomerAddress(data, isShipping);
            }

            $.ceNotification('show', {
              title: _.tr('notice'),
              message: _.tr('is2or_spl_integration.address_generated_successfully'),
              type: 'N'
            });
          } else {
            $.ceNotification('show', {
              title: _.tr('warning'),
              message: response.message,
              type: 'W'
            });
          }
        }
      });
    } else {
      $.ceNotification('show', {
        title: _.tr('warning'),
        message: _.tr('is2or_spl_integration.short_address_is_required'),
        type: 'W'
      });
    }
  });

  function _is2orEnsureHiddenUserDataField(prefix, fieldName) {
    const name = 'user_data[' + prefix + '_' + fieldName + ']';
    let $field = $('[name="' + name + '"]');

    if (!$field.length) {
      const $form = $('[name="profiles_form"], form.cm-ajax, form').first();
      $field = $('<input>', {
        type: 'hidden',
        name: name
      }).appendTo($form.length ? $form : 'body');
    }

    return $field;
  }

  /**
   * Map SPL region names to Saudi Arabia state IDs
   */
  function _is2orMapRegionToStateId(regionName) {
    const stateMapping = {
      'Riyadh': '1',
      'Makkah': '2',
      'Madinah': '3',
      'Qassim': '4',
      'Eastern Province': '5',
      'Asir': '6',
      'Tabuk': '7',
      'Hail': '8',
      'Northern Borders': '9',
      'Jazan': '10',
      'Najran': '11',
      'Bahah': '12',
      'Jawf': '13'
    };

    return stateMapping[regionName] || '';
  }

  /**
   * Fill customer address fields from SPL response in ADMIN backend
   * Admin uses user_data[field] name format, no elm_* ID dependency
   */
  function _is2orFillAdminCustomerAddress(data, isShipping) {
    const prefix = isShipping ? 's' : 'b';
    const spl = _.is2or_spl_integration || {};
    const city = data.City_L2 || data.City || '';
    const region = data.RegionName_L2 || data.RegionName || '';

    // Building No
    const buildingFieldId = isShipping ? (spl.s_building_no_field_id || '') : (spl.b_building_no_field_id || '');
    if (buildingFieldId) {
      $('[name="user_data[fields][' + buildingFieldId + ']"]').val(data.BuildingNumber);
      $('#elm_' + buildingFieldId).val(data.BuildingNumber);
    }

    // Address (Address1 + Address2)
    $('[name="user_data[' + prefix + '_address]"]').val(data.FullAddress);

    // City
    $('[name="user_data[' + prefix + '_city]"]').val(city);

    // Country - always SA
    $('[name="user_data[' + prefix + '_country]"]').val('SA').trigger('change');
    const cityIdFieldSelector = 'user_data[' + prefix + '_city_id]';
    _is2orEnsureHiddenUserDataField(prefix, 'city_id').attr('data-ca-city-id-field', cityIdFieldSelector).val(data.city_id || 0);

    // State/Region - wait for country change to populate state dropdown
    setTimeout(function() {
      const stateId = _is2orMapRegionToStateId(region);
      $('[name="user_data[' + prefix + '_state]"]').filter('select').val(stateId).trigger('change');
    }, 800);

    // Zipcode
    $('[name="user_data[' + prefix + '_zipcode]"]').val(data.PostCode);

    // Coordinates
    $('[name="user_data[' + prefix + '_latitude]"]').val(data.Latitude);
    $('[name="user_data[' + prefix + '_longitude]"]').val(data.Longitude);

    _is2orRefreshMap(data.Latitude, data.Longitude);
  }

  /**
   * Fill customer address fields from SPL response (frontend)
   */
  function _is2orFillCustomerAddress(data, isShipping) {
    const prefix = isShipping ? 's' : 'b';
    const spl = _.is2or_spl_integration || {};

    // Building No - Try multiple selectors
    const buildingFieldId = isShipping ? (spl.s_building_no_field_id || '62') : (spl.b_building_no_field_id || '61');
    let $buildingNo = $('[name="user_data[fields][' + buildingFieldId + ']"]');

    if (!$buildingNo.length) {
      $buildingNo = $('#elm_' + buildingFieldId);
    }

    if ($buildingNo.length) {
      $buildingNo.val(data.BuildingNumber);
    }

    // Address (Address1 + Address2)
    const targetAddressId = isShipping ? (spl.s_address || '19') : (spl.b_address || '18');
    $('#elm_' + targetAddressId).val(data.FullAddress);
    $('[name="user_data[' + prefix + '_address]"]').val(data.FullAddress);

    // City - Use English version
    const targetCityId = isShipping ? (spl.s_city || '23') : (spl.b_city || '22');
    $('#elm_' + targetCityId).val(data.City);
    $('[name="user_data[' + prefix + '_city]"]').val(data.City);

    // Country - always SA
    const targetCountryId = isShipping ? (spl.s_country || '27') : (spl.b_country || '26');
    $('#elm_' + targetCountryId).val('SA').trigger('change');
    $('[name="user_data[' + prefix + '_country]"]').val('SA').trigger('change');
    const cityIdFieldSelector = 'user_data[' + prefix + '_city_id]';
    _is2orEnsureHiddenUserDataField(prefix, 'city_id').attr('data-ca-city-id-field', cityIdFieldSelector).val(data.city_id || 0);

    // State/Region - Map region name to state ID
    setTimeout(function() {
      const stateId = _is2orMapRegionToStateId(data.RegionName);
      const targetStateId = isShipping ? (spl.s_state || '25') : (spl.b_state || '24');

      const $stateSelect = $('#elm_' + targetStateId);
      if ($stateSelect.length && $stateSelect.is('select')) {
        $stateSelect.val(stateId).trigger('change');
      }

      $('[name="user_data[' + prefix + '_state]"]').filter('select').val(stateId).trigger('change');
    }, 800);

    // Zipcode
    const targetZipcodeId = isShipping ? (spl.s_zipcode || '29') : (spl.b_zipcode || '28');
    $('#elm_' + targetZipcodeId).val(data.PostCode);
    $('[name="user_data[' + prefix + '_zipcode]"]').val(data.PostCode);

    // Coordinates (field type L - lat x lon) 
    var $latInput = $('[name="user_data[' + prefix + '_latitude]"]');
    var $lngInput = $('[name="user_data[' + prefix + '_longitude]"]');
    
    $latInput.val(data.Latitude).attr('data-ca-latest-latitude', data.Latitude).trigger('change');
    $lngInput.val(data.Longitude).attr('data-ca-latest-longitude', data.Longitude).trigger('change');

    // Update map marker 
    var latIdAttr = $latInput.attr('id') || '';
    var idMatch = latIdAttr.match(/\d+$/);
    var dynamicFieldId = idMatch ? idMatch[0] : (isShipping ? '57' : '56'); // Fallback
    
    const markerSelector = '.cm-customer-coordinates-map-marker__' + dynamicFieldId;
    $(markerSelector).attr('data-ca-geo-map-marker-lat', data.Latitude)
                    .attr('data-ca-geo-map-marker-lng', data.Longitude);

    _is2orRefreshMap(data.Latitude, data.Longitude);
  }

  /**
   * Fill vendor/company address fields from SPL response
   */
  function _is2orFillVendorAddress(data) {
    // Standard company address fields
    $('[name="company_data[address]"]').val(data.FullAddress);
    $('[name="company_data[city]"]').val(data.City);
    $('[name="company_data[zipcode]"]').val(data.PostCode);
    $('[name="company_data[country]"]').val('SA').trigger('change');

    // SPL specific fields for vendor
    $('[name="company_data[spl_short_address]"]').val(data.ShortAddress);
    $('[name="company_data[spl_building_no]"]').val(data.BuildingNumber);
    $('[name="company_data[spl_street]"]').val(data.Street);
    $('[name="company_data[spl_district]"]').val(data.District);
    $('[name="company_data[spl_additional_number]"]').val(data.AdditionalNumber);

    // Coordinates
    $('[name="company_data[latitude]"]').val(data.Latitude);
    $('[name="company_data[longitude]"]').val(data.Longitude);
    
    _is2orRefreshMap(data.Latitude, data.Longitude);

    // State - Map region name to state ID
    setTimeout(function() {
      const stateId = _is2orMapRegionToStateId(data.RegionName);
      $('[name="company_data[state]"]').filter('select').val(stateId).trigger('change');
    }, 800);
  }

  /**
   * Helper function to force CS-Cart Geo-maps to re-render with new coordinates
   */
  function _is2orRefreshMap(lat, lng) {
      lat = parseFloat(lat);
      lng = parseFloat(lng);
      if (isNaN(lat) || isNaN(lng)) return;
      
      setTimeout(function() {
          var $mapContainers = $('.ty-control-group__map-box .cm-geo-map-container');
          
          $mapContainers.each(function() {
              var $container = $(this);
              
              $container.attr('data-ca-geo-map-initial-lat', lat);
              $container.attr('data-ca-geo-map-initial-lng', lng);
              
              $container.empty();
              $container.removeData();
              
              if (typeof $.ceEvent === 'function') {
                  $.ceEvent('trigger', 'ce.commoninit', [$container.parent()]);
              }
              
              setTimeout(function() {
                  try {
                      $container.ceGeoMap('setCenter', lat, lng);
                      $container.ceGeoMap('removeAllMarkers');
                      $container.ceGeoMap('addMarkers', [{ lat: lat, lng: lng }]);
                  } catch (e) {
                      // 
                  }
              }, 400);
          });
      }, 250);
  }
})(Tygh, Tygh.$);
