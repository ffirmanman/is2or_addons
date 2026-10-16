{* {capture name="add_new_quotation"}
    {include file="addons/sg_quotation/views/sg_quotation/update.tpl"}
{/capture}
{$href="sg_quotation.update"}
{include file="common/popupbox.tpl"
    id="request_quotation"
    text=__("request_quotation")
    href=$href
    title=__("request_quotation")
    link_text=__("request_quotation")
    act="general"
    icon="icon-plus"
    link_class="btn-primary"
} *}

{if $auth.user_id && $product.allow_sg_quotation == 'Y' && !$quick_view}
    <a class="cm-dialog-opener cm-dialog-auto-size ty-btn ty-btn__tertiary ty-btn-icon"
    data-ca-target-id="quotation_popup_{$product.product_id}" title="{__('request_quotation')}">
        <div class="quotation-icon-wrapper">
            <i class="ty-icon ty-icon-doc-text"></i>
            <span class="quote-symbol">$</span>
        </div>
    </a>

    <div id="quotation_popup_{$product.product_id}" class="hidden" title="Request a Quotation">
        {include file="addons/sg_quotation/views/sg_quotation/update.tpl" product=$product}
    </div>

    <style>
        /* [id^="quotation_popup_"] {
            display: none !important;

        } */
        .ut2-pb__button > [id*="add_to_cart"] > div.ui-dialog{
            display: block;
        }
        .quotation-icon-wrapper {
            position: relative;
            display: inline-block;
            font-size: 16px; /* Adjust size as needed */
            color: rgb(246, 247, 248);    /* Standard CS-Cart Blue */
        }

        .quote-symbol {
            position: absolute;
            bottom: -8px;
            right: -5px;
            background: #cbcbcb;
            font-size: 12px;
            font-weight: bold;
            border: 1px solid #f6f8fb;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
{/if}