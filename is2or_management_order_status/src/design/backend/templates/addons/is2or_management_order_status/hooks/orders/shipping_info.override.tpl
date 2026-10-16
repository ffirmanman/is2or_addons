{$packing = $addons.is2or_management_order_status.packing_status}


{if $order_info.status === $packing}
    {hook name="orders:shipping_info"}
        {if $order_info.shipping}
        <div class="control-group shift-top">
            {include file="common/subheader.tpl" title=__("shipping_information")}
            {$cancelled_status}
            {$declined_status}
        </div>
        {assign var="is_group_shippings" value=count($order_info.shipping)>1}

        {foreach from=$order_info.shipping item="shipping" key="shipping_id" name="f_shipp"}

            <div class="control-group" >
                <span> {$shipping.group_name|default:__("none")}</span>
            </div>

            <div class="control-group">
                <div class="control-label">{__("method")}</div>
                <div id="tygh_shipping_info" class="controls">
                    {$shipping.shipping}
                </div>
            </div>

            {if $shipping.shipment_keys}
                {* show created shipments *}
                <p>
                    <strong>{__("track_on_carrier_site")}</strong>
                </p>
                {foreach from=$shipping.shipment_keys item="shipment_key"}
                    {$shipment = $shipments[$shipment_key]}

                    {hook name="orders:data_shipping"}
                        <div class="control-group">
                            <div class="control-label">
                                {if $shipment.carrier_info}
                                    {$shipment.carrier_info.name}
                                {else}
                                    {__("tracking_number")}
                                {/if}
                            </div>
                            <div class="controls">
                                <a class="hand cm-tooltip cm-combination tracking-number-edit-link" title="{__("edit")}" id="sw_tracking_number_{$shipment_key}">
                                    {include_ext file="common/icon.tpl" source="edit" class="flex-inline top"}
                                </a>
                                {if $shipment.carrier_info.tracking_url}
                                    <a href="{$shipment.carrier_info.tracking_url nofilter}" target="_blank" id="on_tracking_number_{$shipment_key}">{if $shipment.tracking_number}{$shipment.tracking_number}{else}&mdash;{/if}</a>
                                {else}
                                    <span id="on_tracking_number_{$shipment_key}">{$shipment.tracking_number}</span>
                                {/if}
                                <div class="hidden" id="tracking_number_{$shipment_key}">
                                    <input class="input-small" type="text" name="update_shipping[{$shipping.group_key}][{$shipment.shipment_id}][tracking_number]" size="45" value="{$shipment.tracking_number}" />
                                    <input type="hidden" name="update_shipping[{$shipping.group_key}][{$shipment.shipment_id}][shipping_id]" value="{$shipping.shipping_id}" />
                                    <input type="hidden" name="update_shipping[{$shipping.group_key}][{$shipment.shipment_id}][carrier]" value="{$shipment.carrier}" />
                                </div>
                            </div>
                        </div>
                    {/hook}
                {/foreach}

            {else}
                {* show form for creating new full shipment *}
                {$shipment_id = 0}
                {$carrier = ""}
                {hook name="orders:new_shipment"}
                    <div class="control-group">
                        <label class="control-label" for="tracking_number_{$shipping.shipping_id}">{__("tracking_number")}</label>
                        <div class="controls">
                            <input id="tracking_number_{$shipping.shipping_id}" class="input-full" type="text" name="update_shipping[{$shipping.group_key}][{$shipment_id}][tracking_number]" size="45" value="" />
                            <input type="hidden" name="update_shipping[{$shipping.group_key}][{$shipment_id}][shipping_id]" value="{$shipping.shipping_id}" />
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="carrier_key_{$shipping.shipping_id}">{__("carrier")}</label>
                        <div class="controls">
                            {include file="common/carriers.tpl" id="carrier_key_{$shipping.shipping_id}" meta="input-full" name="update_shipping[`$shipping.group_key`][`$shipment_id`][carrier]" carrier=$carrier}
                        </div>
                    </div>
                {/hook}
                <hr>
            {/if}
            <div class="clearfix">
                {if $shipping.need_shipment}
                    {if $shipping.shipment_keys}
                        {assign var="shipment_btn" value=__("new_shipment")}
                        {$align="left"}
                    {else}
                        {assign var="shipment_btn" value=__("create_detailed_shipment")}
                        {$align="right"}
                    {/if}
                    <div class="clearfix">
                        {if "shipments.add"|fn_check_view_permissions}
                            {capture name="link_text_icon"}{strip}
                                <span class="flex-inline top">
                                                    {include_ext file="common/icon.tpl" class="icon icon-angle-right"}
                                                </span>
                            {/strip}{/capture}
                            {include file="common/popupbox.tpl" id="add_shipment_`$shipping.group_key`" content="" link_text="`$shipment_btn``$smarty.capture.link_text_icon`" act="link" href=" " link_class="pull-`$align`"}
                        {/if}
                    </div>
                {/if}

                {if $is_group_shippings}<hr>{/if}

                {if $shipping.shipment_keys}
                    {if !$is_group_shippings}
                        <div class="pull-right">
                            <a href="{"shipments.manage?order_id=`$order_info.order_id`"|fn_url}">{__("shipments")}&nbsp;({$order_info.shipment_ids|count})</a>
                        </div>
                    {/if}
                {/if}
            </div>
        {/foreach}

        {if $is_group_shippings}
            <div class="clearfix">
                <a class="pull-right" href="{"shipments.manage?order_id=`$order_info.order_id`"|fn_url}">{__("shipments")}&nbsp;({$order_info.shipment_ids|count})</a>
            </div>
        {/if}
    {else}

        {foreach from=$order_info.product_groups item="group" key="group_id"}
            {if $group.all_free_shipping}
                <div class="clearfix">
                    {if $order_info.need_shipping}
                        {if "shipments.add"|fn_check_view_permissions}
                            <div class="clearfix">
                                {include file="common/popupbox.tpl" id="add_shipment_0" content="" but_text=__("new_shipment") act="create" but_meta="btn"}
                            </div>
                        {/if}
                    {/if}

                    <a class="pull-right" href="{"shipments.manage?order_id=`$order_info.order_id`"|fn_url}">{__("shipments")}&nbsp;({$order_info.shipment_ids|count})</a>
                </div>
            {/if}
        {/foreach}
    {/if}
    {/hook}
{else}
    {hook name="orders:shipping_info"}
        <div></div>
    {/hook}
{/if}
