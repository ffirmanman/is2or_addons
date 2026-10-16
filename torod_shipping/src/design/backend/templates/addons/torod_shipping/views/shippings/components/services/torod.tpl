<fieldset>
    <div class="control-group">
        <label class="control-label" for="torod_service_type">{__("torod_service_type")}</label>
        <div class="controls">
            <select id="torod_service_type" name="shipping_data[service_params][service_type]">
                <option value="normal" {if $shipping.service_params.service_type == "normal"}selected="selected"{/if}>{__("torod_normal")}</option>
                <option value="cold" {if $shipping.service_params.service_type == "cold"}selected="selected"{/if}>{__("torod_cold")}</option>
                <option value="quick" {if $shipping.service_params.service_type == "quick"}selected="selected"{/if}>{__("torod_quick")}</option>
            </select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="courier_code">{__('courier_code')}:</label>
        <div class="controls">
            <input type="text" id="courier_code" name="shipping_data[service_params][courier_code]" value="{$shipping.service_params.courier_code}">
        </div>
    </div>
</fieldset>
