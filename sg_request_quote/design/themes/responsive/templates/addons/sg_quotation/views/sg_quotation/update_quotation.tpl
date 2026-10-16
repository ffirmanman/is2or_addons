{** Quotation Request Form - Frontend *}

{script src="js/tygh/exceptions.js"}

<div class="ty-account">
    <div class="ty-account__section">        
        <form action="{""|fn_url}" method="post" name="quotation_form" id="quotation_form" class="cm-processed-form ty-quotation-form" enctype="multipart/form-data">
            
            {* Personal Information Section *}
            <div class="ty-quotation-section">
                <h3 class="ty-quotation-section__title">Personal Information</h3>
                <div class="ty-quotation-row">
                    {* First Name *}
                    <div class="ty-quotation-col">
                        <div class="ty-control-group">
                            <label class="ty-control-group__label cm-required" for="first_name">
                                {__('first_name')}:
                            </label>
                            <div class="ty-control-group__item">
                                <input type="text" 
                                       id="first_name" 
                                       name="quotation_data[first_name]" 
                                       class="ty-input-text cm-focus" 
                                       placeholder="Enter your first name"
                                       value="{$smarty.session.cart.user_data.firstname|default:''}" 
                                       required />
                            </div>
                        </div>
                    </div>

                    {* Last Name *}
                    <div class="ty-quotation-col">
                        <div class="ty-control-group">
                            <label class="ty-control-group__label cm-required" for="last_name">
                                {__('last_name')}:
                            </label>
                            <div class="ty-control-group__item">
                                <input type="text" 
                                       id="last_name" 
                                       name="quotation_data[last_name]" 
                                       class="ty-input-text" 
                                       placeholder="Enter your last name"
                                       value="{$smarty.session.cart.user_data.lastname|default:''}" 
                                       required />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ty-quotation-row">
                    {* Email *}
                    <div class="ty-quotation-col">
                        <div class="ty-control-group">
                            <label class="ty-control-group__label cm-required cm-email" for="email">
                                {__('email')}:
                            </label>
                            <div class="ty-control-group__item">
                                <input type="email" 
                                       id="email" 
                                       name="quotation_data[customer_email]" 
                                       class="ty-input-text" 
                                       placeholder="Enter your email address"
                                       value="{$smarty.session.cart.user_data.email|default:''}" 
                                       required />
                            </div>
                        </div>
                    </div>

                    {* Mobile Number *}
                    <div class="ty-quotation-col">
                        <div class="ty-control-group">
                            <label class="ty-control-group__label cm-required" for="mobile">
                                {__('phone')}:
                            </label>
                            <div class="ty-control-group__item">
                                <input type="tel" 
                                       id="mobile" 
                                       name="quotation_data[customer_phone]" 
                                       class="ty-input-text" 
                                       placeholder="Enter your mobile number"
                                       value="{$smarty.session.cart.user_data.phone|default:''}" 
                                       required />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {* Products Section *}
            <div class="ty-quotation-section">
                <h3 class="ty-quotation-section__title">Product Information</h3>
                
                {* Products Selection *}
                <div class="ty-control-group">
                    <label class="ty-control-group__label cm-required" for="added_products" style="width: 50%;">
                        Select Products:
                    </label>
                    <div class="ty-control-group__item">
                        {include file="addons/sg_quotation/pickers/products/picker.tpl" 
                            input_name="quotation_data[product_ids]" 
                            data_id="added_products" 
                            item_ids=$selected_product_ids
                            type="table"
                            picker_id="added_products"
                            no_item_text="No products selected"
                            but_text="Add Products"
                            view_mode="mixed"
                            additional_query_params='&sg_quotation_product_picker=true'
                        }
                    </div>
                </div>

                {* Required Quantity *}
                {* <div class="ty-control-group">
                    <label class="ty-control-group__label cm-required" for="required_qty">
                        Required Quantity:
                    </label>
                    <div class="ty-control-group__item">
                        <input type="text" 
                               id="required_qty" 
                               name="quotation_data[required_qty]" 
                               class="ty-input-text" 
                               placeholder="Enter quantity (e.g., 100)"
                               value="{$quotation_data.required_qty|default:''}" 
                               required />
                    </div>
                </div> *}

                {* Additional Notes *}
                <div class="ty-control-group">
                    <label class="ty-control-group__label additionalLabel" for="description">
                        Additional Notes / Requirements:
                    </label>
                    <div class="ty-control-group__item">
                        <textarea id="description" 
                                  name="quotation_data[note]" 
                                  rows="5" 
                                  class="ty-input-textarea" 
                                  placeholder="Please provide any additional information or specific requirements">{$quotation_data.description|default:''}</textarea>
                        <span class="ty-control-group__hint">Optional: Add any special requirements or questions</span>
                    </div>
                </div>
            </div>

            {* Hidden User ID *}
            <input type="hidden" name="quotation_data[user_id]" value="{$auth.user_id}" />
            
            {if $quotation_data.quotation_id}
                <input type="hidden" name="quotation_id" value="{$quotation_data.quotation_id}" />
            {/if}

            {* Submit Buttons *}
            <div class="ty-control-group ty-quotation-form__buttons">
                <button type="submit" 
                        name="dispatch[sg_quotation.save_quotation_multi]" 
                        class="ty-btn ty-btn__primary" style="width: 1200px; max-width:100%;">
                    <i class="ty-icon-ok"></i> Submit Quotation Request
                </button>
                <a href="{"sg_quotation.manage_quotation"|fn_url}" class="ty-btn ty-btn__secondary cancelbtn">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>
<style>
    .additionalLabel{
        width: 100%;
    }

    .ty-quotation-form {
        max-width: 900px;
    }

    .ty-quotation-section {
        margin-bottom: 35px;
        padding: 25px;
        background: #f9f9f9;
        border-radius: 5px;
        border: 1px solid #e5e5e5;
    }

    .ty-btn.ty-btn__secondary.cancelbtn{
        width:200px;
    }

    .ty-quotation-section__title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #333;
        padding-bottom: 10px;
        border-bottom: 2px solid #ddd;
    }

    .ty-quotation-row {
        display: flex;
        gap: 20px;
        margin-bottom: 0;
    }

    .ty-quotation-col {
        flex: 1;
        min-width: 0;
    }

    .ty-control-group {
        margin-bottom: 20px;
    }

    .ty-control-group__label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        font-size: 14px;
        color: #555;
    }

    .ty-control-group__label.cm-required:after {
        content: " *";
        color: #ff0000;
    }

    .ty-control-group__item {
        width: 100%;
    }

    .ty-control-group__hint {
        display: block;
        margin-top: 5px;
        font-size: 12px;
        color: #888;
        font-style: italic;
    }

    .ty-input-text,
    .ty-input-textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d3d3d3;
        border-radius: 3px;
        font-size: 14px;
        box-sizing: border-box;
        transition: border-color 0.3s;
    }

    .ty-input-textarea {
        resize: vertical;
        min-height: 120px;
        font-family: inherit;
    }

    .ty-input-text:focus,
    .ty-input-textarea:focus {
        border-color: #4d90fe;
        outline: none;
        box-shadow: 0 0 0 2px rgba(77, 144, 254, 0.1);
    }

    .ty-quotation-form__buttons {
        display: flex;
        gap: 10px;
        margin-top: 30px;
        padding-top: 20px;
    }

    .ty-btn__big {
        padding: 12px 30px;
        font-size: 16px;
    }

    /* Product Picker Styles */
    .cm-picker-product-options {
        margin-top: 10px;
    }

    @media (max-width: 768px) {
        .ty-quotation-row {
            flex-direction: column;
            gap: 0;
        }
        
        .ty-quotation-section {
            padding: 15px;
        }
        
        .ty-quotation-form__buttons {
            flex-direction: column;
        }
        
        .ty-quotation-form__buttons .ty-btn {
            width: 100%;
        }
    }

    @media (max-width: 450px){
        .ty-btn.ty-btn__secondary.cancelbtn{
            width:1200px;
            max-width: 90%;
            margin-left: 0px;
        }
        .buttons-container.picker.buttons-container-picker{
            height: auto;
        }
        .cm-dialog-closer.ty-btn.ty-float-right{
            padding-top: 10px;
        }
        #content_added_products{
            .ty-buttons-container__add-or-close{
                display: flex;
                flex: 1;
                flex-wrap: wrap;
                flex-grow: 1;
                .ty-btn__secondary.cm-process-items.cm-dialog-closer.ty-btn{
                    flex-basis: 100%;
                    width: 100%;
                    flex-grow: 1;
                }
                .ty-btn__secondary.cm-process-items.ty-btn{
                    flex-basis: 45%;
                    margin-top: 10px;
                    flex-grow: 1;
                }
                .ty-close-text{
                    flex-basis: 45%;
                    margin-top: 0px;
                    flex-grow: 1;
                }
            }
            .cm-item.ty-float-right.ty-product-list__bulk{
                margin-left: 20px;
            }
            .ty-float-right.ty-product-list__bulk{
                position: relative;
                top: 30px;
                z-index: 999999;
            }
            .ty-btn.ty-btn__tertiary.cm-reset-link{
                margin-left: 10px;
            }
        }
        #elm_pagination_steps{
            min-width: 135px;
        }
    }

    .select-vendor .ty-select-block__a .ty-icon.ty-icon-down-micro.ty-select-block__arrow{
        display: none;
    }
    .ui-dialog.ui-widget .ui-dialog-content#content_added_products{
        position: relative;
        #company_id_selector_wrap_{
            width: 300px;
            height: auto;
            max-height: 350px;
            left: 30px !important;
            .ty-select-block__input.cm-ajax-content-input{
                width: 280px;
            }
            #scroller_company_id_selector{
                height: auto;
                max-height: 300px;
                #company_id_selector{
                    height: auto;
                    overflow: auto;
                    max-height: 300px;
                }
            }
        }
    }

    .ui-dialog.ui-widget .ui-dialog-content .ty-search-form__buttons-container.buttons-container{
        margin-left: 0px;
        margin-right: 0px;
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
</style>

<script>
    function enforceNumericLimits() {
        const inputs = document.querySelectorAll('input.short.cm-numeric');

        inputs.forEach(input => {
            input.setAttribute('type', 'number');
            input.setAttribute('min', '1');
            input.setAttribute('max', '99999');

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
    $(document).on('click', '.ty-btn__secondary.cm-process-items', function(){
        // document.addEventListener('DOMContentLoaded', enforceNumericLimits);
        enforceNumericLimits();
    })

    $(document).ready(function() {
        const $input = $('.ty-select-block__input');
        const $popup = $('#scroller_company_id_selector');

        // When popup opens, store reference
        $input.on('focus click', function() {
            positionPopup();
        });

        // Recalculate position when user scrolls or window resizes
        $(window).on('scroll resize', function() {
            if ($popup.is(':visible')) {
                positionPopup();
            }
        });

        function positionPopup() {
            const inputOffset = $input.offset();
            const inputHeight = $input.outerHeight();

            $popup.css({
                position: 'absolute',
                top: inputOffset.top + inputHeight,
                left: inputOffset.left,
                zIndex: 10000 // ensure it's above other elements
            });
        }

        $(document).on('click','form[name="advanced_search_form"] .ty-btn.ty-btn__tertiary.cm-reset-link', function(){
            $('form[name="advanced_search_form"] .cm-picker-value').val('');
            $('form[name="advanced_search_form"] button.ty-btn__secondary.ty-btn').trigger('click');
        })
    });

    $(document).on('click','button.ty-btn__secondary.cm-process-items.ty-btn',function(){
        console.log('clicked');
        $('table#added_products input[type="number"]').each(function(index, element){
            $(element).attr('required','required');
        })
    })
</script>
<style>
#content_added_products{
	.ty-section__body{
		overflow:visible;
	}
	.ty-control-group{
		position:relative;
	}
}
.ui-dialog.ui-widget .ui-dialog-content#content_added_products {
	& #company_id_selector_wrap_{
		left: 0px !important;
		top: 0px !important;
	}
}

form[name="advanced_search_form"] .ty-btn__secondary.cm-dialog-opener{
    margin: 5px;
}
</style>

{capture name="mainbox_title"}{__("request_quotation")}{/capture}
{capture name="page_title"}{__("request_quotation")}{/capture}