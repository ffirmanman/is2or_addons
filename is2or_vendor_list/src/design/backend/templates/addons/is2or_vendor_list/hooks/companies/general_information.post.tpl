<div class="control-group">
    <label class="control-label">{__("is2or_vendor_list.vendor_categories")}:</label>
    <div class="controls">
        {$is2or_vendor_category_picker_suffix=$id|default:"new"}
        {$is2or_vendor_category_picker_id="is2or_vendor_category_ids_`$is2or_vendor_category_picker_suffix`"}
        {include
            file="pickers/categories/picker.tpl"
            multiple=true
            input_name="company_data[is2or_vendor_category_ids]"
            item_ids=$company_data.is2or_vendor_category_ids|default:""
            data_id=$is2or_vendor_category_picker_id
            no_item_text=__("is2or_vendor_list.all_categories")
            use_keys="N"
            owner_company_id=""
            but_meta="pull-right"
        }
        <p class="muted description">{__("is2or_vendor_list.vendor_categories.tooltip")}</p>
    </div>
</div>
