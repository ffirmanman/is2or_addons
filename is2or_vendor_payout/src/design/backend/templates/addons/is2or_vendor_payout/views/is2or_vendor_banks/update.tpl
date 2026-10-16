{if $vendor_bank}
    {assign var="id" value=$vendor_bank.id}
{else}
    {assign var="id" value=0}
{/if}

<form class="form-edit form-horizontal" method="POST" action="{""|fn_url}">
    <input type="hidden" name="id" value="{$id}" />
    <input type="hidden" name="company_id" value="{$company_id}" />

    <div class="control-group">
        <label class="control-label cm-required" for="elm_country">{__("country")}</label>
        <div class="controls">
            <select id="elm_country" name="data[country]" class="form-control cm-object-picker input-large">
                {foreach from=$countries item="country" key="code"}
                <option value="{$code}" {if $vendor_bank.country == $code}selected="selected"{/if}>{$country}</option>
                {/foreach}
            </select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label cm-required" for="elm_city">{__('city')}</label>
        <div class="controls">
            <input type="text" class="form-control input-large" id="elm_city" name="data[city]" value="{$vendor_bank.city}" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label cm-required" for="elm_bank_name">{__("is2or_vendor_payout.bank_name")}</label>
        <div class="controls">
            <input type="text" id="elm_bank_name" name="data[bank_name]" value="{$vendor_bank.bank_name}" class="form-control input-large" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label cm-required" for="elm_account_name">{__("is2or_vendor_payout.account_name")}</label>
        <div class="controls">
            <input type="text" id="elm_account_name" name="data[account_name]" value="{$vendor_bank.account_name}" class="form-control input-large" />
        </div>
    </div>

    {* <div class="control-group">
        <label class="control-label cm-required" for="elm_account_number">{__("is2or_vendor_payout.account_number")}</label>
        <div class="controls">
            <input type="text" id="elm_account_number" name="data[account_number]" value="{$vendor_bank.account_number}" class="form-control input-large" />
        </div>
    </div> *}

    {* <div class="control-group">
        <label class="control-label cm-required" for="elm_swift_code">{__("is2or_vendor_payout.swift_code")}</label>
        <div class="controls">
            <input type="text" id="elm_swift_code" name="data[swift_code]" value="{$vendor_bank.swift_code}" class="form-control input-large" />
        </div>
    </div> *}

    <div class="control-group">
        <label class="control-label cm-required" for="elm_iban">{__("is2or_vendor_payout.iban")}</label>
        <div class="controls">
            <input type="text" id="elm_iban" name="data[iban]" value="{$vendor_bank.iban}" class="form-control input-large" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label cm-required" for="elm_mobile">{__("is2or_vendor_payout.mobile")}</label>
        <div class="controls">
            <input type="text" id="elm_mobile" name="data[mobile]" value="{$vendor_bank.mobile}" class="form-control input-large cm-phone" />
        </div>
    </div>

    {include file="common/select_status.tpl"
        input_name="data[status]"
        id="elm_bank_status"
        obj_id=$id
        obj=$vendor_bank
        hidden=false
    }

    <div class="buttons-container">
        {include file="buttons/save_cancel.tpl"
            but_name="dispatch[is2or_vendor_banks.update]"
            cancel_action="close"
            save=$id
        }
    </div>
</form>