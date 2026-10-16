{if $field.field_type == 'J'}
    {$disabled = $shipping_flag && !$ship_to_another}
    <div class="control-group">
        <label class="control-label" for="{$element_id}" {if $required == "Y"}class="required"{/if}>{$field.description}:</label>
        <div class="controls">
            {** Customer profile context (admin editing user profile) **}
            {if $element_id}
                <input type="text"
                    id="{$element_id}"
                    name="{$data_name}[{$data_id}]"
                    value="{$value}"
                    class="ty-input-text input-medium"
                    {$disabled_param nofilter} />
                <button
                    data-ca-element-id="{$element_id}"
                    data-ca-element-type="{$section}"
                    type="button"
                    class="btn btn-primary is2or-spl-integration-generate-address"
                    {if $disabled}disabled="disabled"{/if}>
                    {__('is2or_spl_integration.generate_address')}
                </button>
            {else}
                {** Vendor/Company context **}
                <input type="text"
                    id="elm_company_spl_short_address"
                    name="company_data[spl_short_address]"
                    value="{$company_data.spl_short_address}"
                    class="ty-input-text input-medium" />
                <button
                    data-ca-element-id="elm_company_spl_short_address"
                    data-ca-element-type="V"
                    type="button"
                    class="btn btn-primary is2or-spl-integration-generate-address"
                    {if $disabled}disabled="disabled"{/if}>
                    {__('is2or_spl_integration.generate_address')}
                </button>
            {/if}
        </div>
    </div>
{/if}