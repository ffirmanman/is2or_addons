
<div class="sidebar-row">
<h6>{__("admin_search_title")}</h6>

<form action="{""|fn_url}" name="tickets_search_form" method="get" class="{$form_meta}">
{capture name="simple_search"}

{if $smarty.request.redirect_url}
<input type="hidden" name="redirect_url" value="{$smarty.request.redirect_url}" />
{/if}

    <div class="sidebar-field">
        <label for="order_id">{__("order_id")}</label>
        <input type="text" name="order_id" id="order_id" value="{$search.order_id}" size="10" />
    </div>

    <div class="sidebar-field">
        <label for="customer">{__("customer")}</label>
        <input type="text" name="customer" id="customer" value="{$search.customer}" size="30" />
    </div>

    <div class="sidebar-field">
        <label for="email">{__("email")}</label>
        <input type="text" name="email" id="email" value="{$search.email}" size="30" />
    </div>

    <div class="sidebar-field">
        <label for="status">{__("status")}</label>
        <select name="status" class="ty-input-text-medium" id="status">
                <option value="">Select Status</option>
                <option value="O" {if $search.status == 'O'}selected="selected"{/if}>{__('new')}</option>
                <option value="A" {if $search.status == 'A'}selected="selected"{/if}>{__('approved')}</option>
                <option value="D" {if $search.status == 'D'}selected="selected"{/if}>{__('not_approved')}</option>
                <option value="C" {if $search.status == 'C'}selected="selected"{/if}>{__('completed')}</option>
        </select>
    </div>

    <div class="control-group">
        <div class="controls">
            {include file="common/period_selector.tpl" period=$search.period form_name="trr_log_search_form" nowrap=false}
        </div>
    </div>

{/capture}

{include file="common/advanced_search.tpl" simple_search=$smarty.capture.simple_search dispatch=$dispatch view_type="tickets" in_popup=false}

</form>

</div><hr>
<style>
    .calendar{
        margin:5px;
    }
</style>