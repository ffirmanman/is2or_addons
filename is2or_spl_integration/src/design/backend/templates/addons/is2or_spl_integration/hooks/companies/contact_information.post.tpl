{** IS2OR SPL Integration - Vendor/Company Address Fields (Admin Backend) **}

<div class="control-group">
    <label class="control-label">{__("is2or_spl_integration.spl_short_address")}:</label>
    <div class="controls">
        <input type="text"
               id="elm_company_spl_short_address"
               name="company_data[spl_short_address]"
               value="{$company_data.spl_short_address}"
               class="input-medium" />
        <button data-ca-element-id="elm_company_spl_short_address"
                data-ca-element-type="V"
                type="button"
                class="btn btn-primary is2or-spl-integration-generate-address">
            {__("is2or_spl_integration.generate_address")}
        </button>
    </div>
</div>

<div class="control-group">
    <label class="control-label">{__("is2or_spl_integration.building_no")}:</label>
    <div class="controls">
        <input type="text"
               name="company_data[spl_building_no]"
               value="{$company_data.spl_building_no}"
               class="input-medium" />
    </div>
</div>

<div class="control-group">
    <label class="control-label">{__("is2or_spl_integration.street")}:</label>
    <div class="controls">
        <input type="text"
               name="company_data[spl_street]"
               value="{$company_data.spl_street}"
               class="input-medium" />
    </div>
</div>

<div class="control-group">
    <label class="control-label">{__("is2or_spl_integration.district")}:</label>
    <div class="controls">
        <input type="text"
               name="company_data[spl_district]"
               value="{$company_data.spl_district}"
               class="input-medium" />
    </div>
</div>

<div class="control-group">
    <label class="control-label">{__("is2or_spl_integration.additional_number")}:</label>
    <div class="controls">
        <input type="text"
               name="company_data[spl_additional_number]"
               value="{$company_data.spl_additional_number}"
               class="input-medium" />
    </div>
</div>

{** SPL Address Verification Status **}
{if $company_data.company_id}
<hr />
<div class="control-group">
    <label class="control-label">{__("is2or_spl_integration.verification_status")}:</label>
    <div class="controls">
        {assign var="spl_status" value=$company_data.spl_verification_status|default:"pending"}
        {if $spl_status == "verified"}
            <span class="label label-success">{__("is2or_spl_integration.status_verified")}</span>
        {elseif $spl_status == "failed"}
            <span class="label label-important">{__("is2or_spl_integration.status_failed")}</span>
        {elseif $spl_status == "overridden"}
            <span class="label label-warning">{__("is2or_spl_integration.status_overridden")}</span>
        {else}
            <span class="label">{__("is2or_spl_integration.status_pending")}</span>
        {/if}
    </div>
</div>

{if $company_data.spl_verified_at}
<div class="control-group">
    <label class="control-label">{__("is2or_spl_integration.verified_at")}:</label>
    <div class="controls">
        <span>{$company_data.spl_verified_at|date_format:"%d %b %Y %H:%M"}</span>
    </div>
</div>
{/if}

{if $company_data.spl_verification_message}
<div class="control-group">
    <label class="control-label">{__("is2or_spl_integration.verification_message")}:</label>
    <div class="controls">
        <span>{$company_data.spl_verification_message}</span>
    </div>
</div>
{/if}

<div class="control-group">
    <div class="controls">
        <button type="button"
                class="btn cm-ajax"
                data-ca-url="{"is2or_spl_integration.reverify&company_id=`$company_data.company_id`"|fn_url}"
                data-ca-target-form="none"
                onclick="$.ceAjax('request', $(this).data('ca-url'), { method: 'post', callback: function() { location.reload(); } });">
            <i class="icon-refresh"></i> {__("is2or_spl_integration.reverify")}
        </button>

        {if $spl_status != "overridden"}
        <button type="button"
                class="btn btn-warning cm-ajax"
                data-ca-url="{"is2or_spl_integration.override&company_id=`$company_data.company_id`"|fn_url}"
                data-ca-target-form="none"
                onclick="$.ceAjax('request', $(this).data('ca-url'), { method: 'post', callback: function() { location.reload(); } });">
            <i class="icon-ok"></i> {__("is2or_spl_integration.override_publish")}
        </button>
        {else}
        <button type="button"
                class="btn cm-ajax"
                data-ca-url="{"is2or_spl_integration.clear_override&company_id=`$company_data.company_id`"|fn_url}"
                data-ca-target-form="none"
                onclick="$.ceAjax('request', $(this).data('ca-url'), { method: 'post', callback: function() { location.reload(); } });">
            <i class="icon-remove"></i> {__("is2or_spl_integration.clear_override")}
        </button>
        {/if}
    </div>
</div>
{/if}
