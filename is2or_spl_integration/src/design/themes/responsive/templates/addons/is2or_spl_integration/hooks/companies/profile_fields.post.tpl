{** IS2OR SPL Integration - Vendor/Company Address Fields **}
{** Hook: companies:profile_fields_post or similar **}

<div class="ty-control-group">
    <label class="ty-control-group__title">{__("is2or_spl_integration.spl_short_address")}:</label>
    <input type="text"
           id="elm_company_spl_short_address"
           name="company_data[spl_short_address]"
           value="{$company_data.spl_short_address}"
           class="ty-input-text input-medium" />
    <button data-ca-element-id="elm_company_spl_short_address"
            data-ca-element-type="V"
            type="button"
            class="ty-btn ty-btn__primary is2or-spl-integration-generate-address">
        {__("is2or_spl_integration.generate_address")}
    </button>
</div>

<div class="ty-control-group">
    <label class="ty-control-group__title">{__("is2or_spl_integration.building_no")}:</label>
    <input type="text"
           name="company_data[spl_building_no]"
           value="{$company_data.spl_building_no}"
           class="ty-input-text input-medium" />
</div>

<div class="ty-control-group">
    <label class="ty-control-group__title">{__("is2or_spl_integration.street")}:</label>
    <input type="text"
           name="company_data[spl_street]"
           value="{$company_data.spl_street}"
           class="ty-input-text input-medium" />
</div>

<div class="ty-control-group">
    <label class="ty-control-group__title">{__("is2or_spl_integration.district")}:</label>
    <input type="text"
           name="company_data[spl_district]"
           value="{$company_data.spl_district}"
           class="ty-input-text input-medium" />
</div>

<div class="ty-control-group">
    <label class="ty-control-group__title">{__("is2or_spl_integration.additional_number")}:</label>
    <input type="text"
           name="company_data[spl_additional_number]"
           value="{$company_data.spl_additional_number}"
           class="ty-input-text input-medium" />
</div>
