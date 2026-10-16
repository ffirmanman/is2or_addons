<div class="quotation-popup-wrapper">
    <form id="quotation_form" name="quotation_form" class="cm-ajax cm-form" method="post" action="{""|fn_url}">
        <input type="hidden" name="dispatch" value="quotation.save_quotation">
        <input type="hidden" name="quotation_data[quotation_id]" value="{$quotationData.quotation_id}">
        <input type="hidden" name="quotation_data[total]" value="{$quotationData.total}">
        <div class="quotation-customer-info">
            {if $quotationData.product_data}
                <table class="table table-middle table--relative table-responsive sortable">
                    <thead>           
                        <tr>
                            <th>{__("total_price")}</th>
                        </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td data-th="{__("total_price")}">{$quotationData.total|default:"0.00"|fn_format_price:$primary_currency:null:false}</td>
                    </tr>
                    </tbody>
                </table>
            {/if}

            <div class="ty-control-group control-group">
                <label for="discount" class="cm-required">{__('discount')}:</label>
                <div class="controls">
                    <select name="quotation_data[discount_type]">
                        <option value="A" {if $quotationData.discount_type == 'A'}selected='selected'{/if}>{__('absolute')}</option>
                        <option value="P" {if $quotationData.discount_type == 'P'}selected='selected'{/if}>{__('percentage')}</option>
                    </select>
                    <input class="input-medium" data-a-dec="." data-a-sep="" data-a-sign="" type="number" id="discount" name="quotation_data[discount]" value="{$quotationData.discount}" min="0" step="0.1">
                </div>
            </div>

        </div>

        <div class="buttons-container">
            {include file="buttons/save_cancel.tpl" but_name="dispatch[sg_quotation.apply_discount]" but_meta='cm-ajax cm-dialog-closer' cancel_action="close" extra="" hide_first_button=$hide_first_button save=true but_text=__('apply_discount') approve_text=__('apply_discount')}
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