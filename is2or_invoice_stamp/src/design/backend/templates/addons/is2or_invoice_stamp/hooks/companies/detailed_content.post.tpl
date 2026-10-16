{include file="common/subheader.tpl" title=__("is2or_invoice_stamp.stamp_and_sign") target="#acc_addon_invoice_stamp"}

<div class="collapsed in" id="acc_addon_invoice_stamp">
    <div class="control-group">
        <label class="control-label">{__("is2or_invoice_stamp.stamp")}:</label>
        <div class="controls">
            {include file="common/attach_images.tpl"
                image_name="vendor_stamp"
                image_object_type="vendor_stamp"
                image_pair=$company_data.vendor_stamp
                image_object_id=$company_data.company_id
                no_detailed=true
                hide_titles=true
            }
        </div>
    </div>

    <div class="control-group">
        <label class="control-label">{__("is2or_invoice_stamp.sign")}:</label>
        <div class="controls">
            {include file="common/attach_images.tpl"
                image_name="vendor_sign"
                image_object_type="vendor_sign"
                image_pair=$company_data.vendor_sign
                image_object_id=$company_data.company_id
                no_detailed=true
                hide_titles=true
            }
        </div>
    </div>
</div>