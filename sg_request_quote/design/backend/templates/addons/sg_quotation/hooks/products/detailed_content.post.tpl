{include file="common/subheader.tpl" title=__("quotation") target="#acc_quotation"}
<div id="acc_quotation" class="collapse in">
    <div class="control-group">
        <label class="control-label" for="allow_quotation">{__("allow_quotation")}:</label>
        <div class="controls">
            <label class="checkbox">
            <input type="hidden" name="product_data[allow_sg_quotation]" id="allow_sg_quotation" value="N" />
            <input type="checkbox" name="product_data[allow_sg_quotation]" value="Y" {if $product_data.allow_sg_quotation == "Y"}checked="checked"{/if} />
            </label>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="sg_quotation_note">{__("sg_quotation_note")}:</label>
        <div class="controls">
                <textarea name="product_data[sg_quotation_note]" id="sg_quotation_note" cols="50" rows="5" class="cm-wysiwyg input-textarea-long input-fill">{$product_data.sg_quotation_note}</textarea>
            </div>
    </div>
</div>