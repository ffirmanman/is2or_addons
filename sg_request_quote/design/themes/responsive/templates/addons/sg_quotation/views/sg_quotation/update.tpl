{** Quotation Request Popup Template **}

<div class="quotation-popup-wrapper">
    <form id="quotation_form" name="quotation_form" class="cm-ajax cm-form" method="post" action="{""|fn_url}">
        <input type="hidden" name="quotation_data[product_id]" value="{$product.product_id}">

        <div class="quotation-product-info">
            <div class="qp-image">
                {if $product.main_pair}
                    {include file="common/image.tpl" images=$product.main_pair image_width=120 image_height=120}
                {else}
                    <img src="{$images_dir}/no_image.png" width="120" height="120" alt="{$product.product}" />
                {/if}
            </div>

            <div class="qp-details">
                <h3 class="qp-name">{$product.product nofilter}</h3>
                <p class="qp-price">
                    {if $product.price}
                        {include file="common/price.tpl" value=$product.price}
                    {/if}
                </p>
            </div>
        </div>
        
        <div  class="ty-wysiwyg-content content-description" data-ca-accordion-is-active-scroll-to-elm="1">{$product.sg_quotation_note nofilter}</div>
        {* <div class="content-wrapper">
            <p id="seo-text">
                {$product.sg_quotation_note|truncate:400:"...":true}
            </p>
            <span id="read-more-btn">... Read More</span>
        </div> *}
        <hr>

        <div class="quotation-customer-info">
            <h4>{__('quotation_details')}</h4>
            
            <p>{__('please_enter_quotation_value')}</p>

            <div class="control-group">
                <label for="q_name" class="cm-required">{__('full_name')}:</label>
                <input type="text" name="quotation_data[customer_name]" id="q_name" class="input-text" required value="{$smarty.session.cart.user_data.firstname} {$smarty.session.cart.user_data.lastname}">
            </div>

            <div class="control-group">
                <label for="q_email" class="cm-required cm-email">{__('email_address')}:</label>
                <input type="text" name="quotation_data[customer_email]" id="q_email" class="input-text" required value="{$smarty.session.cart.user_data.email}">
            </div>

            <div class="control-group">
                <label for="q_phone">{__('phone_number')}:</label>
                <input type="text" name="quotation_data[customer_phone]" id="q_phone" class="input-text" value="{$smarty.session.cart.user_data.phone}">
            </div>

            <div class="control-group">
                <label for="q_quantity" class="cm-required">{__('required_quantity')}:</label>
                <input type="number" name="quotation_data[required_qty]" id="q_quantity" class="input-text" min="1"  max="99999" value="1" required>
            </div>

            <div class="control-group">
                <label for="q_notes">{__('additional_notes')}:</label>
                <textarea name="quotation_data[note]" id="q_notes" class="input-textarea" placeholder="Enter your requirements or special requests"></textarea>
            </div>
        </div>
        <div class="buttons-container center" >
            {include file="buttons/save.tpl"
                cancel_action="close"
                hide_first_button=!$fields|default:[]
                but_text=__("save")
                but_meta="cm-submit ty-btn__primary"
                but_name="dispatch[sg_quotation.save_quotation]"
            }
            
        </div>
    </form>
</div>
<style>
    .quotation-popup-wrapper {
        max-width: 500px;
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
    .ui-dialog .buttons-container {
        margin-left: -20px !important; 
    }
    
    #read-more-btn {
        color: #369; /* CS-Cart Blue */
        cursor: pointer;
        font-weight: bold;
        display: none; /* Hidden by default, JS reveals it */
        margin-left: 5px;
        text-decoration: underline;
    }

    #read-more-btn:hover {
        color: #23527c;
    }

    #seo-text {
        display: inline; /* Keeps the button on the same line as text */
    }
    
</style>

<script>
    $(document).ready(function(){
        $.ceEvent('on', 'ce.formpost_add_products', function(frm, elm) {
            console.log(frm,elm);
        })

    })
</script>