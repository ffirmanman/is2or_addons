<fieldset>
    <div class="control-group">
        <label class="control-label" for="tryoto_delivery_type">{__("tryoto_delivery_type")}</label>
        <div class="controls">
            <select id="tryoto_delivery_type" name="shipping_data[service_params][pickup_type]">
                <option value="toCustomerDoorstep " {if $shipping.service_params.pickup_type == "toCustomerDoorstep "}selected="selected"{/if}>{__("tryoto_to_customer_doorstep ")}</option>
                <option value="pickupByCustomer" {if $shipping.service_params.pickup_type == "pickupByCustomer"}selected="selected"{/if}>{__("tryoto_pickup_by_customer")}</option>
                <option value="toCustomerDoorstepOrPickupByCustomer" {if $shipping.service_params.pickup_type == "toCustomerDoorstepOrPickupByCustomer"}selected="selected"{/if}>{__("tryoto_to_customer_doorstep_or_pickup_by_customer")}</option>
            </select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="courier_code">{__('courier_code')}:</label>
        <div class="controls">
            <input type="text" id="courier_code" name="shipping_data[service_params][courier_code]" value="{$shipping.service_params.courier_code}">
        </div>
    </div>

{*    <div class="control-group">*}
{*        <label class="control-label" for="test_mode">{__("test_mode")}</label>*}
{*        <input type="hidden" name="shipping_data[service_params][tryoto_provider_id]" value="" />*}
{*        <input type="hidden" name="shipping_data[service_params][tryoto_provider_name]" value="" />*}

{*        <div class="controls">*}
{*            <input type="hidden" name="shipping_data[service_params][test_mode]" value="N" />*}
{*            <input id="test_mode" type="checkbox" name="shipping_data[service_params][test_mode]" value="Y" {if $shipping.service_params.test_mode == "Y"}checked="checked"{/if} />*}
{*        </div>*}
{*    </div>*}


</fieldset>
