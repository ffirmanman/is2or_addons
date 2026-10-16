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
        >{__("is2or_spl_integration.generate_address")}</button>
    </div>
{elseif $field.field_type == 'L'}
    {if $section == 'S'}
        {$latitude = $profile_data.s_latitude|default:""}
        {$longitude = $profile_data.s_longitude|default:""}
        {$latitude_name = 'user_data[s_latitude]'}
        {$longitude_name = 'user_data[s_longitude]'}
    {else}
        {$latitude = $profile_data.b_latitude|default:""}
        {$longitude = $profile_data.b_longitude|default:""}
        {$latitude_name = 'user_data[b_latitude]'}
        {$longitude_name = 'user_data[b_longitude]'}
    {/if}

    {$disabled = $shipping_flag && !$ship_to_another}

    {include file="addons/is2or_customer_coordinates/pickers/map.tpl" id=$field.field_id latitude=$latitude longitude=$longitude}

    <div class="ty-control-group">
        <label class="ty-control-group__title {if $required == "Y"}cm-required{/if}">{__('coordinates')}  ({__("latitude_short")} &times; {__("longitude_short")}):</label>
        <label class="control-label {if $required == "Y"}cm-required{/if} hidden" for="elm_latitude_{$field.field_id}">{__("latitude")}</label>
        <label class="control-label {if $required == "Y"}cm-required{/if} hidden" for="elm_longitude_{$field.field_id}">{__("longitude")}</label>

        <input type="text" name="{$latitude_name}" id="elm_latitude_{$field.field_id}" value="{$latitude}" data-ca-latest-latitude="{$latitude}" class="input-small" {$disabled_param nofilter}>
        &times;
        <input type="text" name="{$longitude_name}" id="elm_longitude_{$field.field_id}" value="{$longitude}" data-ca-latest-longitude="{$longitude}" class="input-small" {$disabled_param nofilter}>

        <button type="button" class="ty-btn ty-btn__secondary cm-dialog-opener cm-hide-with-inputs customer-coordinates-picker-opener" data-ca-target-id="map_picker_{$field.field_id}" {if $disabled}disabled="disabled"{/if}>{__("select")}</button>
    </div>
{/if}
