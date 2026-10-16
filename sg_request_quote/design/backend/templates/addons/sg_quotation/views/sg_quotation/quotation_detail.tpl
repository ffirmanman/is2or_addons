<div class="quotation-popup-wrapper">
    <form id="quotation_form" name="quotation_form" class="cm-ajax cm-form" method="post" action="{""|fn_url}">
        <input type="hidden" name="dispatch" value="quotation.save_quotation">
        <input type="hidden" name="quotation_data[product_id]" value="{$product.product_id}">
        <input type="hidden" name="quotation_data[price]" value="{$product.price}">
        <input type="hidden" name="quotation_data[quotation_id]" value="{$quotationData.quotation_id}">
        <div class="quotation-customer-info">
            {* <div class="ty-control-group control-group">
                <label for="q_name" class="">{__('product')}:</label>
                <div class="controls">
                    <a href="{fn_url('products.update&product_id=')}{$quotationData.product_id}">{$quotationData.product_name}</a>
                </div>
            </div> *}
            
            {if $quotationData.product_data}
                <table class="table table-middle table--relative table-responsive sortable">
                    <thead>           
                        <tr>
                            <th>{__("product_name")}</th>
                            <th>{__("required_qty")}</th>
                            <th>{__("price")}</th>
                        </tr>
                    </thead>
                    <tbody>
                    {foreach from=$quotationData.product_data item="prod"}
                        <tr>
                            <td data-th="{__("product_name")}">{$prod.product_name}</td>
                            <td data-th="{__("required_qty")}">{$prod.required_qty}</td>
                            <td data-th="{__("price")}">{$prod.price|default:"0.00"|fn_format_price:$primary_currency:null:false}</td>
                        </tr>
                    {/foreach}
                    <tr>
                        <td data-th="{__("total")}">{__('total')}</td>
                        <td data-th="{__("required_qty")}">{$quotationData.totalQuantity}</td>
                        <td data-th="{__("price")}">{$quotationData.total|default:"0.00"|fn_format_price:$primary_currency:null:false}</td>
                    </tr>
                    {if $quotationData.discount}
                        <tr>
                            <td class="hidden-phone">{__('discount')}</td>
                            <td></td>
                            <td data-th="{__("discount")}">{include file="common/price.tpl" value=$quotationData.discounted_price|fn_format_price}</td>
                        </tr>
                        <tr>
                            <td class="hidden-phone">{__('quotation_total')}</td>
                            <td></td>
                            <td data-th="{__("quotation_total")}">{include file="common/price.tpl" value=($quotationData.total-$quotationData.discounted_price)|fn_format_price}</td>
                        </tr>
                    {/if}
                    </tbody>
                </table>
            {/if}

            <div class="ty-control-group control-group">
                <label for="q_name" class="">{__('full_name')}:</label>
                <div class="controls">
                    <input type="text" name="quotation_data[customer_name]" id="q_name" class="input-text" disabled="disabled" value="{$quotationData.customer_name}">
                </div>
            </div>

            <div class="ty-control-group control-group">
                <label for="q_email" class=" cm-email">{__('email')}:</label>
                <div class="controls">
                    <input type="email" name="quotation_data[customer_email]" id="q_email" class="input-text" disabled="disabled" required value="{$quotationData.customer_email}">
                </div>
            </div>

            <div class="ty-control-group control-group">
                <label for="q_phone" class="cm-phone">{__('phone')}:</label>
                <div class="controls">
                    <input type="text" name="quotation_data[customer_phone]" id="q_phone" class="input-text" disabled="disabled" value="{$quotationData.customer_phone}">
                </div>
            </div>

            <div class="ty-control-group control-group">
                <label for="q_notes">{__('notes')}:</label>
                <div class="controls">
                    {* <textarea name="quotation_data[note]" id="q_notes" class="input-textarea" disabled="disabled">{$quotationData.note}</textarea> *}
                    <p>{$quotationData.note nofilter}</p>
                </div>
            </div>

            {if $quote.order_id}
            <div class="ty-control-group control-group">
                <label for="order_id">{__('order_id')}:</label>
                <div class="controls">
                    <a href="{'orders.details&order_id='|fn_url}{$quote.order_id}">{$quote.order_id}</a>
                </div>
            </div>
            {/if}

            <div class="ty-control-group control-group">
                <label for="status">{__('sg_status.status')}:</label>
                {if $quotationData.status == "D"}
                    <span class="cm-status-d ">
                        <span class="label label-important">{__("not_approved")}</span>
                    </span>
                {elseif $quotationData.status == "C"}
                    <span class="cm-status-c ">
                        <span class="label label-important">{__("completed")}</span>
                    </span>
                {elseif $quotationData.status == "A"}
                    <span class="cm-status-a">
                        <span class="label label-success">{__("approved")}</span>
                    </span>
                {elseif $quotationData.status == "O"}
                    <span class="cm-status-o ">
                        <span class="label label-important">{__("new")}</span>
                    </span>
                {/if}
            </div>

            {if $quotationData.approved_by && $quotationData.status == "A"}
                <div class="ty-control-group control-group">
                    <label for="approved_by">{__('approved_by')}:</label>
                    <div class="controls">
                        <a href="{'profiles.update&user_id='|fn_url}{$quotationData.approved_by}">{$quotationData.approved_by|fn_get_user_name}({$quotationData.approved_by|fn_get_user_email})</a>
                    </div>
                </div>
            {/if}
        </div>

        <div class="buttons-container">
            {if $quotationData.status == "D"}
                {include file="addons/sg_quotation/buttons/save_close.tpl" but_name="dispatch[sg_quotation.approve_quotation]" but_meta='cm-ajax cm-dialog-closer' cancel_action="close" extra="" hide_first_button=$hide_first_button save=true but_text=__('approve') approve_text=__('approve') approve=true approve_dispatch="dispatch[sg_quotation.approve_quotation]"}
            {elseif $quotationData.status == "C"}
                <a class="cm-dialog-closer cm-inline-dialog-closer cm-cancel tool-link btn ">{__("close")}</a>
            {elseif $quotationData.status == "A"}
                {include file="addons/sg_quotation/buttons/save_close.tpl" but_name="dispatch[sg_quotation.disapprove_quotation]" but_meta='cm-ajax cm-dialog-closer' cancel_action="close" extra="" hide_first_button=$hide_first_button save=true but_text=__('disapprove') disapprove_text=__('disapprove') disapprove=true disapprove_dispatch="dispatch[sg_quotation.disapprove_quotation]" quotationID=$quotationData.quotation_id}
            {elseif $quotationData.status == "O"}
                {include file="addons/sg_quotation/buttons/save_close.tpl" but_name="dispatch[sg_quotation.disapprove_quotation]" but_meta='cm-ajax cm-dialog-closer' cancel_action="close" extra="" hide_first_button=$hide_first_button save=true but_text=__('disapprove') disapprove_text=__('disapprove') disapprove=true disapprove_dispatch="dispatch[sg_quotation.disapprove_quotation]" approve_text=__('approve') approve=true approve_dispatch="dispatch[sg_quotation.approve_quotation]" quotationID=$quotationData.quotation_id}
            {/if}
        </div>
    </form>
</div>
<style>
    .quotation-popup-wrapper {
        padding: 15px;
        width: 100%;
    }
    .quotation-product-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .qp-image img {
        border-radius: 8px;
        border: 1px solid #ddd;
    }
    .qp-name {
        margin: 0;
        font-size: 1.2em;
    }
    .control-group {
        margin-bottom: 10px;
    }
    .input-text,
    .input-textarea {
        width: 100%;
        padding: 8px;
    }
    .buttons-container {
        margin-top: 15px;
    }
</style>