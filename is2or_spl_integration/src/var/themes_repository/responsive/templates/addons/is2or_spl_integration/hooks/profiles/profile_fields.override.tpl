{* ============================================================
   1. Field type J  — SPL Short Address (unchanged)
   ============================================================ *}
{if $field.field_type == 'J'}
    {$disabled = $shipping_flag && !$ship_to_another}

    <div class="ty-control-group">
        <label class="ty-control-group__title {if $required == "Y"}cm-required{/if}" for="{$element_id}">{$field.description}:</label>
        <input type="text" id="{$element_id}" value="{$value}" name="{$data_name}[{$data_id}]" class="input-medium" {$disabled_param nofilter} />
        <button
            data-ca-element-id="{$element_id}"
            data-ca-element-type="{$section}"
            type="button"
            class="ty-btn ty-btn__primary is2or-spl-integration-generate-address"
            {if $disabled}disabled="disabled"{/if}
        >{__('is2or_spl_integration.generate_address')}</button>
    </div>
{/if}


{* ============================================================
   2. Field City — Google Places Autocomplete
   ============================================================ *}
{if $field.field_name == "s_city" || $field.field_name == "b_city"}

    {* Render city input *}
    <div class="ty-control-group">
        <label class="ty-control-group__title {if $required == "Y"}cm-required{/if}" for="{$element_id}">{$field.description}</label>

        <input
            type="text"
            id="{$element_id}"
            name="{$data_name}[{$data_id}]"
            size="32"
            value="{$value}"
            class="ty-input-text is2or-city-autocomplete {if !$skip_field}{$_class}{else}cm-skip-avail-switch{/if} {if $section == "S"}cm-location-shipping{else}cm-location-billing{/if}"
            autocomplete="off"
        />
    </div>

    {* Initialize autocomplete — inject only once per element_id *}
    <script>
    (function($) {
        'use strict';

        var ELEMENT_ID   = '{$element_id}';
        var COUNTRY_CODE = '{$profile_data.s_country|default:"sa"}';
        var DATA_KEY     = 'is2or-city-init';

        /* Attach autocomplete to input */
        function attachAutocomplete() {
            var $input = $('#' + ELEMENT_ID);
            if (!$input.length || $input.data(DATA_KEY)) {
                return;
            }

            try {
                var ac = new google.maps.places.Autocomplete($input[0], {
                    types: ['(cities)']
                });

                ac.addListener('place_changed', function() {
                    var place = ac.getPlace();
                    if (place && place.name) {
                        $input.val(place.name).trigger('change');
                    }
                });

                $input.data(DATA_KEY, true);
                console.log('[is2or] Autocomplete active → #' + ELEMENT_ID);

            } catch (err) {
                console.error('[is2or] Autocomplete error on #' + ELEMENT_ID, err);
            }
        }

        /* Wait until Google Maps is available, max 30 seconds */
        function waitForGoogle(callback) {
            var attempts = 0;
            var timer = setInterval(function() {
                if (typeof google !== 'undefined' && google.maps && google.maps.places) {
                    clearInterval(timer);
                    callback();
                } else if (++attempts >= 100) {
                    clearInterval(timer);
                    console.info('[is2or] Google Maps not ready, city input as normal text (#' + ELEMENT_ID + ')');
                }
            }, 300);
        }

        function bindEvents() {
            if (typeof window.Tygh !== 'undefined' && window.Tygh.$) {
                window.Tygh.$.ceEvent('on', 'ce.commoninit', function(context) {
                    if ($('#' + ELEMENT_ID, context).length && !$('#' + ELEMENT_ID, context).data(DATA_KEY)) {
                        waitForGoogle(attachAutocomplete);
                    }
                });
            }

            $(document).ready(function() {
                waitForGoogle(attachAutocomplete);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindEvents);
        } else {
            bindEvents();
        }

    }(typeof Tygh !== 'undefined' ? Tygh.$ : jQuery));
    </script>

    <style>
        .pac-container {
            z-index: 9999 !important;
            border-radius: 4px;
        }
        .is2or-pac-wrapper {
            display: block;
            position: relative;
            width: 100%;
        }
        .is2or-pac-wrapper input[type="text"] {
            width: 100%;
            box-sizing: border-box;
        }
        /* Autocomplete container styling */
        .pac-container {
            z-index: 9999 !important;
        }
    </style>
{/if}