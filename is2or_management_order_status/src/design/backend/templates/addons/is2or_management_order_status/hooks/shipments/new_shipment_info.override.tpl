<fieldset>
    <div class="control-group">
        <label class="control-label" for="shipping_name">{__("shipping_method")}</label>
        <div class="controls">
            <select name="shipment_data[shipping_id]" id="shipping_name">
                {foreach from=$shippings item="shipping"}
                    <option value="{$shipping.shipping_id}" {if $current_shipping_id === $shipping.shipping_id}selected{/if}>{$shipping.shipping}</option>
                {/foreach}
            </select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="tracking_number">{__("tracking_number")}</label>
        <div class="controls">
            <input type="text" name="shipment_data[tracking_number]" id="tracking_number" size="10" value="" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="carrier_key">{__("carrier")}</label>
        <div class="controls">
            {include file="common/carriers.tpl" id="carrier_key" name="shipment_data[carrier]"}
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="shipment_comments">{__("comments")}</label>
        <div class="controls">
            <textarea id="shipmentcomments" name="shipment_data[comments]" cols="55" rows="8" class="span9"></textarea>
        </div>
    </div>
</fieldset>
