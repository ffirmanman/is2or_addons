{if $field.field_type == 'L'}
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

    <div class="ty-control-group">
        <label class="ty-control-group__title {if $required == "Y"}cm-required{/if}">{__('coordinates')}:</label>
        <label class="control-label {if $required == "Y"}cm-required{/if} hidden" for="elm_latitude_{$field.field_id}">{__("latitude")}</label>
        <label class="control-label {if $required == "Y"}cm-required{/if} hidden" for="elm_longitude_{$field.field_id}">{__("longitude")}</label>

        <input type="text" name="{$latitude_name}" id="elm_latitude_{$field.field_id}" value="{$latitude}" data-ca-latest-latitude="{$latitude}" class="input-small hidden" {$disabled_param nofilter}>
        <input type="text" name="{$longitude_name}" id="elm_longitude_{$field.field_id}" value="{$longitude}" data-ca-latest-longitude="{$longitude}" class="input-small hidden" {$disabled_param nofilter}>

        <div class="ty-control-group__map-box" style="margin-top: 15px; width: 100%; max-width: 665px; height: 396px; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
            {include file="addons/is2or_customer_coordinates/pickers/map.tpl" id=$field.field_id latitude=$latitude longitude=$longitude}
        </div>
    </div>
{/if}