{hook name="orders:advanced_search"}
    <input type="hidden" name="selected_tab" value="{$active_tab}" />
    <div class="group">
        <div class="control-group">
            <label class="control-label">{__("manager")}</label>
            <div class="controls">
                <div class="nowrap">
                    <div class="row-fluid ">
                        <div class="span4">
                            <input type="text" name="issuer" id="issuer" value="{$search.issuer}" size="30" />
                        </div>
                        <div class="span6 checkbox-list">
                            <label for="no_issuer">
                                <input type="checkbox" id="no_issuer" name="no_issuer" value="Y" {if $search.no_issuer}checked="checked"{/if}>{__("no_manager_assigned")}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="group form-horizontal">
        <div class="control-group">
            <label class="control-label">{__("period")}</label>
            <div class="controls">
                {include file="common/period_selector.tpl" period=$search.period form_name="orders_search_form"}
            </div>
        </div>
    </div>

    <div class="row-fluid">
        <div class="group span6 form-horizontal">
            <div class="control-group">
                <label class="control-label" for="tax_exempt">{__("tax_exempt")}</label>
                <div class="controls">
                    <select name="tax_exempt" id="tax_exempt">
                        <option value="">--</option>
                        <option value="Y" {if $search.tax_exempt == "Y"}selected="selected"{/if}>{__("yes")}</option>
                        <option value="N" {if $search.tax_exempt == "N"}selected="selected"{/if}>{__("no")}</option>
                    </select>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="order_id">{__("order_id")}</label>
                <div class="controls">
                    <input type="text" name="order_id" id="order_id" value="{$search.order_id}" size="10"/>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="elm_company">{__("company")}</label>
                <div class="controls">
                    <input type="text" name="company" id="elm_company" value="{$search.company}" size="10"/>
                </div>
            </div>
        </div>

        <div class="group span6 form-horizontal">
            <div class="control-group">
                <label class="control-label" for="has_credit_memo">{__("has_credit_memo")}</label>
                <div class="controls">
                    <input type="checkbox" name="has_credit_memo" id="has_credit_memo" value="Y"{if $search.has_credit_memo} checked="checked"{/if} />
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="crmemo_id">{__("credit_memo_id")}</label>
                <div class="controls">
                    <input type="text" name="credit_memo_id" id="crmemo_id" value="{$search.credit_memo_id}" size="10"/>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="has_invoice">{__("has_invoice")}</label>
                <div class="controls">
                    <input type="checkbox" name="has_invoice" id="has_invoice" value="Y"{if $search.has_invoice} checked="checked"{/if} />
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="inv_id">{__("invoice_id")}</label>
                <div class="controls">
                    <input type="text" name="invoice_id" id="inv_id" value="{$search.invoice_id}" size="10"/>
                </div>
            </div>
            {include file="common/select_vendor.tpl"}
        </div>
    </div>
    <div class="group">
        <div class="control-group">
            <label class="control-label">{__("shipping")}</label>
            <div class="controls checkbox-list">
                {html_checkboxes name="shippings" options=$shippings selected=$search.shippings columns=4}
            </div>
        </div>
    </div>

    <div class="group">
        <div class="control-group">
            <label class="control-label">{__("payment_methods")}</label>
            <div class="controls checkbox-list">
                {html_checkboxes name="payments" options=$payments selected=$search.payments columns=4}
            </div>
        </div>
    </div>
    <div class="group">
        <div class="control-group">
            <label class="control-label">{__("ordered_products")}</label>
            <div class="controls ">
                {include file="common/products_to_search.tpl" placement="right"}
            </div>
        </div>
    </div>
{/hook}
