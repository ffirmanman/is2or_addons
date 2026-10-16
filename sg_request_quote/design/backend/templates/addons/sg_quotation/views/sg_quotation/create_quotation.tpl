{capture name="mainbox"}

    {$image_width = $settings.Thumbnails.product_admin_mini_icon_width}
    {$image_height = $settings.Thumbnails.product_admin_mini_icon_height}
    
    <form action="{""|fn_url}" method="post" target="_self" class=" form-horizontal form-edit" name="quotation_form" enctype="multipart/form-data">
        <div class="table-responsive-wrapper longtap-selection">
            
            <div class="ty-control-group control-group">
                <label class="ty-control-group__title cm-required cm-trim control-label" for="added_products">{__("products_in")}</label>
                <div class="controls">
                    {include file="addons/sg_quotation/pickers/products/picker.tpl" input_name="quotation_data[products]" data_id="added_products" picker_id="added_products" item_ids=$quotation_data.products type="table" no_item_text=__("add_product") additional_query_params='&sg_quotation_product_picker=true'}
                </div>
            </div>

            <div class="ty-control-group control-group">
                <label class="ty-control-group__title cm-required cm-trim control-label">{__("user")}</label>
                <div class="controls">
                    {include file="views/profiles/components/picker/picker.tpl"
                        input_name="quotation_data[user_id]"
                        select_class="cm-object-customer-add"
                        item_ids=[]
                        autofocus=$autofocus
                        url="profiles.get_customer_list?lang_code=`$descr_sl`"
                        dropdown_css_class="select2-dropdown-below-customer-add"
                        users_shared_force=$users_shared_force
                        no_container=false
                        
                    }
                </div>
            </div>

            <div id="content_user" class="hidden">
                {include file="views/profiles/components/profiles_scripts.tpl"}
                {include file="pickers/users/picker.tpl" no_item_text=__("no_items") data_id="sales_rep_users" input_name="table_data[conditions][user]" item_ids=$conditions.user placement="right" but_meta="btn" but_icon="icon-plus"}
            </div>

            <div class="ty-control-group control-group">
                <label for="note" class="ty-control-group__title cm-required cm-trim control-label ">{__("note")}</label>
                <div class="controls">
                    <textarea id="note" name="quotation_data[note]" cols="72" rows="4" class="ty-input-text-full cm-wysiwyg">{$quotation_data.description}</textarea>
                </div>
            </div>
        </div>
    </form>

    
{/capture}

{capture name="buttons"}
    {include file="buttons/save_cancel.tpl" but_name="dispatch[sg_quotation.save_quotation]" but_role="submit-link" save=$id but_target_form="quotation_form"}
{/capture}

{include file="common/mainbox.tpl" title=__("sg_add_quotation") content=$smarty.capture.mainbox title_extra=$smarty.capture.title_extra tools=$smarty.capture.tools adv_buttons=$smarty.capture.adv_buttons buttons=$smarty.capture.buttons}

<style>

    .object-picker__advanced-btn{
        top: 0px;
    }
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Hide arrows in Firefox */
    input[type=number] {
        -moz-appearance: textfield;
    }

    .object-picker__advanced.object-picker__advanced--profiles{
        display: none;
    }

    .cm-external-click.btn{
        margin: 3px;
    }

</style>


<script>
    function enforceNumericLimits() {
        const inputs = document.querySelectorAll('.input-micro.cm-value-decimal.product-picker__amount');

        inputs.forEach(input => {
            input.setAttribute('type', 'number');
            input.setAttribute('min', '1');
            input.setAttribute('max', '999');

            // Prevent non-numeric characters
            input.addEventListener('keypress', (e) => {
                if (e.charCode < 48 || e.charCode > 57) {
                    e.preventDefault();
                }
            });

            // Ensure value stays within range and max 3 digits
            input.addEventListener('input', (e) => {
                let val = e.target.value;

                // Strip non-numeric characters (e.g., pasted text)
                val = val.replace(/\D/g, '');

                // Limit to 3 digits
                if (val.length > 3) {
                    val = val.slice(0, 3);
                }

                // Enforce numeric limits
                if (val !== '') {
                    const num = parseInt(val, 10);
                    if (num < 1) val = '1';
                    if (num > 999) val = '999';
                }

                e.target.value = val;
            });
        });
    }

    // Run on page load
    $(document).on('click', '.btn.cm-process-items', function(){
        // document.addEventListener('DOMContentLoaded', enforceNumericLimits);
        enforceNumericLimits();
    })

    $(document).on('click','#add_product input.btn.cm-process-items.btn-primary',function(){
        console.log('clicked');
        $('tbody#added_products input[type="number"]').each(function(index, element){
            $(element).attr('required','required');
        })
    })
</script>
