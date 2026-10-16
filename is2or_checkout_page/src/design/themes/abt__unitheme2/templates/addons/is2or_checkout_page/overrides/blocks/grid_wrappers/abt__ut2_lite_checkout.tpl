{** IS2OR Override: abt__ut2_lite_checkout.tpl **}
{** New checkout page design **}

{** Inject CSS **}
<link rel="stylesheet" type="text/css" href="{$config.current_location}/design/themes/abt__unitheme2/css/addons/is2or_checkout_page/checkout.css" />

<style>
/* ================================================================
   VENDOR SHIPPING BLOCK
================================================================ */

/* Wrapper antar vendor — beri jarak saat lebih dari 1 vendor */
.is2or-vendor-shipping {
    margin-top: 16px;
}
.is2or-vendor-shipping + .is2or-vendor-shipping {
    border-top: 1px solid #e8e8e8;
    padding-top: 16px;
    margin-top: 16px;
}

/* Label nama vendor */
.is2or-vendor-shipping__label {
    font-size: 13px;
    font-weight: 600;
    color: #555;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

/* Error state pada selected header ketika belum pilih */
.is2or-shipping-selected--error {
    border: 1.5px solid #e53935 !important;
    border-radius: 8px;
}
.is2or-shipping-selected--error .is2or-shipping-selected__name--empty {
    color: #e53935;
}

/* Place Order button disabled */
.is2or-place-order-btn--disabled,
.is2or-place-order-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

/* ================================================================
   PROMO / COUPON INPUT ROW
================================================================ */

.is2or-promo-row {
    display: flex;
    gap: 8px;
    align-items: center;
}

.is2or-promo-input {
    flex: 1;
    min-width: 0;
}

.is2or-promo-apply-btn {
    flex-shrink: 0;
    padding: 0 18px;
    height: 38px;
    background: #f47b20;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
    white-space: nowrap;
}
.is2or-promo-apply-btn:hover {
    background: #d9600a;
}
.is2or-promo-apply-btn:active {
    background: #c05509;
}

/* Feedback message bawah input */
.is2or-coupon-msg {
    margin-top: 6px;
    font-size: 12px;
    padding: 6px 10px;
    border-radius: 5px;
}
.is2or-coupon-msg--success {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #a5d6a7;
}
.is2or-coupon-msg--error {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ef9a9a;
}
.is2or-promo-spinner {
    display: none;
    width: 16px;
    height: 16px;
    border: 2px solid #e0e0e0;
    border-top-color: #f47b20;
    border-radius: 50%;
    animation: is2or-spin 0.7s linear infinite;
    flex-shrink: 0;
}
.is2or-promo-spinner--visible {
    display: inline-block;
}
@keyframes is2or-spin {
    to { transform: rotate(360deg); }
}

.is2or-applied-promos {
    margin-top: 8px;
}

.is2or-applied-promo {
    display: flex;
    flex-direction: column;
    padding: 6px 10px;
    margin-top: 4px;
    background: #e8f5e9;
    border: 1px solid #a5d6a7;
    border-radius: 5px;
}

.is2or-applied-promo__name {
    font-size: 12px;
    font-weight: 600;
    color: #2e7d32;
}

.is2or-applied-promo__desc {
    font-size: 11px;
    color: #388e3c;
    margin-top: 2px;
}

</style>

{** Login popup for non-logged users **}
{if !$auth.user_id}
    <div id="litecheckout_login_block" class="hidden" title="{__("sign_in")}">
        <div class="ty-login-popup">
            {include file="views/auth/login_form.tpl"
                style="popup"
                id="litecheckout_login_block_inner"
            }
        </div>
    </div>
{/if}

<div class="litecheckout litecheckout__form" id="litecheckout_form">
    <h1 class="litecheckout__page-title">{__("checkout")}</h1>

    <div class="is2or-checkout-wrapper">

        {** ===== LEFT COLUMN ===== **}
        <div class="is2or-checkout-left">

            {** --- DELIVERING TO --- **}
            {if $cart.user_data}
            <div class="row-fluid litecheckout__pm_sf">
                <div class="is2or-address-card">
                    <div class="is2or-address-card__icon">
                        <i class="ty-icon-location"></i>
                    </div>
                    <div class="is2or-address-card__body">
                        <div class="is2or-address-card__label">{__("delivering_to")}:</div>
                        {if $cart.user_data.profile_name}
                            <div class="is2or-address-card__name">{$cart.user_data.profile_name}</div>
                        {/if}
                        {if $cart.user_data.s_firstname || $cart.user_data.s_lastname}
                            {assign var="address_name" value="`$cart.user_data.s_firstname` `$cart.user_data.s_lastname`"}
                        {elseif $cart.user_data.firstname || $cart.user_data.lastname}
                            {assign var="address_name" value="`$cart.user_data.firstname` `$cart.user_data.lastname`"}
                        {else}
                            {assign var="address_name" value=""}
                        {/if}
                        <div class="is2or-address-card__detail">
                            {$cart.user_data.s_address|default:$cart.user_data.b_address}
                            {if $cart.user_data.s_city|default:$cart.user_data.b_city}
                                , {$cart.user_data.s_city|default:$cart.user_data.b_city}
                            {/if}
                            {if $cart.user_data.s_state|default:$cart.user_data.b_state}
                                , {$cart.user_data.s_state|default:$cart.user_data.b_state}
                            {/if}
                            {if $cart.user_data.s_zipcode|default:$cart.user_data.b_zipcode}
                                {$cart.user_data.s_zipcode|default:$cart.user_data.b_zipcode}
                            {/if}
                            {if $cart.user_data.s_country_descr|default:$cart.user_data.b_country_descr}
                                , {$cart.user_data.s_country_descr|default:$cart.user_data.b_country_descr}
                            {/if}
                        </div>
                        {if $cart.user_data.phone}
                            <div class="is2or-address-card__detail">
                                {__("recipient")}: {$address_name|trim}
                            </div>
                            <div class="is2or-address-card__detail">
                                {__("phone")}: {$cart.user_data.phone}
                            </div>
                        {/if}
                    </div>
                    {if $auth.user_id && $allow_multiple_profiles}
                        <button type="button"
                                class="is2or-address-card__change"
                                onclick="is2orOpenAddressPopup()">
                            {__("change")}
                        </button>
                    {/if}
                </div>
            </div>
            {/if}

            {** --- YOUR ORDER --- **}
            <div class="is2or-card" id="cart_items">
                <div class="is2or-order-header">
                    <span class="is2or-card__title" style="margin-bottom:0;">{__("your_order")}</span>
                    <a href="{"checkout.cart"|fn_url}" class="is2or-order-edit">
                        <i class="ty-icon-edit"></i>&nbsp;{__("edit")}
                    </a>
                </div>

                <div class="is2or-products-list">
                    {foreach from=$cart_products key="key" item="product"}
                        {if !$cart.products.$key.extra.parent}
                            <div class="is2or-product-item">
                                {** Image **}
                                <div class="is2or-product-item__image">
                                    {if $product.main_pair}
                                        {include file="common/image.tpl"
                                            images=$product.main_pair
                                            obj_id=$product.product_id
                                            image_width=72
                                            image_height=72
                                            lazy_load=false}
                                    {/if}
                                </div>

                                {** Info **}
                                <div class="is2or-product-item__info">
                                    <span class="is2or-product-item__name">
                                        <a href="{"products.view?product_id=`$product.product_id`"|fn_url}">
                                            {$product.product nofilter}
                                        </a>
                                    </span>
                                    {if $product.variation_features}
                                        {foreach $product.variation_features as $v_feature}
                                            <div class="is2or-product-item__meta">
                                                {$v_feature.description}: <b>{$v_feature.variant}</b>
                                            </div>
                                        {/foreach}
                                    {/if}
                                    {if $product.product_options}
                                        <div class="is2or-product-item__meta">
                                            {include file="common/options_info.tpl"
                                                product_options=$product.product_options
                                                no_block=true}
                                        </div>
                                    {/if}
                                    {if $product.company_name}
                                        <div class="is2or-product-item__meta">
                                            {__("vendor")}: {$product.company_name}
                                        </div>
                                    {/if}
                                </div>

                                {** Pricing **}
                                <div class="is2or-product-item__pricing">
                                    <span class="is2or-product-item__unit">
                                        {__("unit_price")}: {include file="common/price.tpl" value=$product.display_price}
                                    </span>
                                    <span class="is2or-product-item__qty">
                                        {__("quantity")}: {$product.amount}
                                    </span>
                                    <span class="is2or-product-item__subtotal">
                                        {include file="common/price.tpl" value=$product.display_subtotal}
                                    </span>
                                </div>

                                {** Remove **}
                                {if !$product.exclude_from_calculate}
                                    <a href="{"checkout.delete?cart_id=`$key`&redirect_mode=checkout"|fn_url}"
                                       class="is2or-product-item__remove cm-ajax"
                                       data-ca-target-id="cart_items,checkout_totals,cart_status*"
                                       title="{__("remove")}">
                                        <i class="ty-icon-cancel-circle"></i>
                                    </a>
                                {/if}
                            </div>
                        {/if}
                    {/foreach}
                </div>
            </div>

            {** --- CHOOSE DELIVERY OPTION --- **}
            {if $cart.shipping_required}
            <div class="is2or-card" id="shipping_rates_list">
                <span class="is2or-card__title">{__("choose_delivery_option")}</span>

                <input type="hidden"
                    name="additional_result_ids[]"
                    value="litecheckout_final_section,litecheckout_step_payment,checkout*"
                    form="litecheckout_payments_form"
                />

                {** ================================================================
                    PER-VENDOR SHIPPING BLOCKS
                    Loop setiap product_group = setiap vendor.
                    Tiap vendor memiliki dropdown + tab kategori sendiri-sendiri.
                    Field submit: shipping_ids[{group_key}] — tidak berubah.
                ================================================================ **}

                {foreach $product_groups as $group_key => $group}
                    {if $group.shipping_by_marketplace}{continue}{/if}
                    {if !$group.shippings || $group.all_edp_free_shipping || $group.shipping_no_required}{continue}{/if}

                    {** Build per-vendor categorized lists **}
                    {assign var="v_cat_instant" value=[]}
                    {assign var="v_cat_sameday" value=[]}
                    {assign var="v_cat_regular" value=[]}
                    {assign var="v_cat_bulky"   value=[]}

                    {foreach $all_shippings.$group_key as $shipping_id => $item}
                        {if $group.shippings.$shipping_id}
                            {$s = $group.shippings.$shipping_id}
                            {$s.shipping  = $item.shipping}
                            {if !$s.delivery_time && $s.service_delivery_time}
                                {$s.delivery_time = $s.service_delivery_time}
                            {/if}
                            {$s.group_key = $group_key}

                            {** Categorize **}
                            {if $s.service_code == "sameDay"}
                                {assign var="dt_lower" value=$s.delivery_time|lower}
                                {if ("hour"|strstr:$dt_lower || "hr"|strstr:$dt_lower)}
                                    {assign var="dt_num" value=$s.delivery_time|regex_replace:"/[^0-9].*/":""}
                                    {if $dt_num <= 4}
                                        {$v_cat_instant[] = $s}
                                    {else}
                                        {$v_cat_sameday[] = $s}
                                    {/if}
                                {else}
                                    {$v_cat_sameday[] = $s}
                                {/if}
                            {elseif $s.service_code == "heavyAndBulky"}
                                {$v_cat_bulky[] = $s}
                            {elseif $s.service_code == "express"}
                                {$v_cat_regular[] = $s}
                            {else}
                                {$v_cat_regular[] = $s}
                            {/if}
                        {/if}
                    {/foreach}

                    {** Resolve chosen shipping for this vendor **}
                    {assign var="v_chosen" value=null}
                    {if $cart.chosen_shipping.$group_key && $group.shippings.{$cart.chosen_shipping.$group_key}}
                        {$v_chosen = $group.shippings.{$cart.chosen_shipping.$group_key}}
                        {$v_chosen.shipping = $all_shippings.$group_key.{$cart.chosen_shipping.$group_key}.shipping}
                        {if !$v_chosen.delivery_time && $v_chosen.service_delivery_time}
                            {$v_chosen.delivery_time = $v_chosen.service_delivery_time}
                        {/if}
                    {/if}

                    {** Determine which tab to show first (prefer regular, fallback to available) **}
                    {if $v_cat_regular}
                        {assign var="v_default_cat" value="regular"}
                    {elseif $v_cat_sameday}
                        {assign var="v_default_cat" value="sameday"}
                    {elseif $v_cat_instant}
                        {assign var="v_default_cat" value="instant"}
                    {else}
                        {assign var="v_default_cat" value="bulky"}
                    {/if}

                    {** Vendor name: from group or fallback **}
                    {assign var="v_company_name" value=$group.company_name|default:$group.name|default:__("vendor")}

                    {** ===== VENDOR SHIPPING BLOCK ===== **}
                    <div class="is2or-vendor-shipping"
                         id="is2or_vendor_shipping_{$group_key}"
                         data-group-key="{$group_key}">

                        {** Vendor label **}
                        <div class="is2or-vendor-shipping__label">{$v_company_name}</div>

                        {** Selected display + dropdown trigger **}
                        <div class="is2or-shipping-selected"
                             id="is2or_shipping_selected_{$group_key}"
                             onclick="is2orToggleVendorDropdown('{$group_key}')">
                            <div class="is2or-shipping-selected__row">
                                <div class="is2or-shipping-selected__left">
                                    {if $v_chosen}
                                        {if $v_chosen.image}
                                            <div class="is2or-shipping-selected__logo">
                                                {include file="common/image.tpl"
                                                    obj_id=$v_chosen.shipping_id
                                                    images=$v_chosen.image
                                                    image_width=40
                                                    image_height=20}
                                            </div>
                                        {/if}
                                        <div class="is2or-shipping-selected__info">
                                            <div class="is2or-shipping-selected__name"
                                                 id="is2or_vship_name_{$group_key}">
                                                {$v_chosen.shipping}
                                                {if $v_chosen.rate|floatval}
                                                    ({include file="common/price.tpl" value=$v_chosen.rate})
                                                {/if}
                                            </div>
                                            {if $v_chosen.delivery_time}
                                                <div class="is2or-shipping-selected__eta"
                                                     id="is2or_vship_eta_{$group_key}">
                                                    {__("estimated_delivery")}: {$v_chosen.delivery_time}
                                                </div>
                                            {/if}
                                        </div>
                                    {else}
                                        <div class="is2or-shipping-selected__info">
                                            <div class="is2or-shipping-selected__name is2or-shipping-selected__name--empty"
                                                 id="is2or_vship_name_{$group_key}">
                                                {__("select_shipping_method")}
                                            </div>
                                            <div class="is2or-shipping-selected__eta"
                                                 id="is2or_vship_eta_{$group_key}"></div>
                                        </div>
                                    {/if}
                                </div>
                                <div class="is2or-shipping-selected__chevron"
                                     id="is2or_shipping_chevron_{$group_key}">▾</div>
                            </div>
                        </div>

                        {** Dropdown: tabs + shipping items **}
                        <div class="is2or-shipping-dropdown"
                             id="is2or_shipping_dropdown_{$group_key}"
                             style="display:none;">

                            {** Category tabs **}
                            <div class="is2or-shipping-cats">
                                {if $v_cat_instant}
                                    <button type="button"
                                            class="is2or-shipping-cat-btn{if $v_default_cat == 'instant'} is2or-shipping-cat-btn--active{/if}"
                                            onclick="is2orShowVendorCat('{$group_key}', 'instant')">
                                        ⚡ {__("instant")}
                                    </button>
                                {/if}
                                {if $v_cat_sameday}
                                    <button type="button"
                                            class="is2or-shipping-cat-btn{if $v_default_cat == 'sameday'} is2or-shipping-cat-btn--active{/if}"
                                            onclick="is2orShowVendorCat('{$group_key}', 'sameday')">
                                        🕐 {__("same_day")}
                                    </button>
                                {/if}
                                {if $v_cat_regular}
                                    <button type="button"
                                            class="is2or-shipping-cat-btn{if $v_default_cat == 'regular'} is2or-shipping-cat-btn--active{/if}"
                                            onclick="is2orShowVendorCat('{$group_key}', 'regular')">
                                        📦 {__("regular")}
                                    </button>
                                {/if}
                                {if $v_cat_bulky}
                                    <button type="button"
                                            class="is2or-shipping-cat-btn{if $v_default_cat == 'bulky'} is2or-shipping-cat-btn--active{/if}"
                                            onclick="is2orShowVendorCat('{$group_key}', 'bulky')">
                                        🏗 {__("bulky_shipping")}
                                    </button>
                                {/if}
                            </div>

                            {** Instant list **}
                            {if $v_cat_instant}
                                <div class="is2or-shipping-cat-list"
                                     id="is2or_vcat_{$group_key}_instant"
                                     style="{if $v_default_cat != 'instant'}display:none;{/if}">
                                    {foreach $v_cat_instant as $s}
                                        {include file="addons/is2or_checkout_page/components/shipping_item.tpl" shipping=$s}
                                    {/foreach}
                                </div>
                            {/if}

                            {** Same Day list **}
                            {if $v_cat_sameday}
                                <div class="is2or-shipping-cat-list"
                                     id="is2or_vcat_{$group_key}_sameday"
                                     style="{if $v_default_cat != 'sameday'}display:none;{/if}">
                                    {foreach $v_cat_sameday as $s}
                                        {include file="addons/is2or_checkout_page/components/shipping_item.tpl" shipping=$s}
                                    {/foreach}
                                </div>
                            {/if}

                            {** Regular list **}
                            {if $v_cat_regular}
                                <div class="is2or-shipping-cat-list"
                                     id="is2or_vcat_{$group_key}_regular"
                                     style="{if $v_default_cat != 'regular'}display:none;{/if}">
                                    {foreach $v_cat_regular as $s}
                                        {include file="addons/is2or_checkout_page/components/shipping_item.tpl" shipping=$s}
                                    {/foreach}
                                </div>
                            {/if}

                            {** Bulky list **}
                            {if $v_cat_bulky}
                                <div class="is2or-shipping-cat-list"
                                     id="is2or_vcat_{$group_key}_bulky"
                                     style="{if $v_default_cat != 'bulky'}display:none;{/if}">
                                    {foreach $v_cat_bulky as $s}
                                        {include file="addons/is2or_checkout_page/components/shipping_item.tpl" shipping=$s}
                                    {/foreach}
                                </div>
                            {/if}

                        </div>
                        {** END DROPDOWN **}

                    </div>
                    {** END VENDOR SHIPPING BLOCK **}

                {/foreach}
                {** END VENDOR LOOP **}

            </div>
            {/if}            

            {** Hidden form fields required by lite checkout JS **}
            <div data-ca-lite-checkout-element="form">
                <form name="litecheckout_payments_form"
                    id="litecheckout_payments_form"
                    action="{"checkout.place_order"|fn_url}"
                    method="post"
                    data-ca-lite-checkout-element="checkout-form"
                    data-ca-lite-checkout-ready-for-checkout="false"
                    class="litecheckout__payment-methods">
                    <input type="hidden" value="1" name="ship_to_another"
                        data-ca-lite-checkout-field="ship_to_another"
                        data-ca-lite-checkout-auto-save-on-change="true">
                    <input type="hidden" name="dispatch" value="checkout.place_order" />
                    <input type="hidden" name="security_hash" class="cm-no-hide-input" value="{""|fn_generate_security_hash}" />
                    <div style="display:none;">{$content nofilter}</div>
                </form>
            </div>
            {hook name="is2or:also_bought"}
            {/hook}
        </div>
        {** END LEFT COLUMN **}


        {** ===== RIGHT COLUMN ===== **}
        <div class="is2or-checkout-right">

                
            <div class="row-fluid litecheckout__pm_sf">
                {** --- PAY WITH --- **}
                {if $payment_methods}
                    <div style="border-bottom: 2px solid #c2c2c2; padding-bottom: 20px;">
                        <span class="is2or-card__title">{__("pay_with")}</span>

                        {** Find active gateway from cart **}
                        {assign var="active_gateway" value="cc"}

                        {** Selected display **}
                        <div class="is2or-gateway-selected" id="is2or_gateway_selected" onclick="is2orToggleGatewayDropdown()">
                            <div class="is2or-gateway-selected__inner">
                                <div class="is2or-gateway-selected__logos" id="is2or_gateway_logos">
                                    {** Default show cc logos **}
                                    <img src="{$config.current_location}/design/themes/responsive/templates/addons/amazon_payment_services/images/mastercard-logo.png" height="19" />
                                </div>
                                <div class="is2or-gateway-selected__name" id="is2or_gateway_name">Master Card</div>
                            </div>
                            <div class="is2or-gateway-selected__chevron" id="is2or_gateway_chevron">▾</div>
                        </div>

                        {** Dropdown list **}
                        <div class="is2or-gateway-dropdown" id="is2or_gateway_dropdown" style="display:none;">
                            
                            {** CC - Visa, MasterCard, Amex, Mada **}
                            <label class="is2or-gateway-item is2or-gateway-item--active" onclick="is2orSelectGateway('cc', 'Visa', ['visa-logo.png'])">
                                <input type="radio" 
                                    name="payment_data[aps][gateway]" 
                                    value="cc"
                                    form="litecheckout_payments_form"
                                    checked
                                    style="display:none;" />
                                <div class="is2or-gateway-item__logos">
                                    <img src="{$config.current_location}/design/themes/responsive/templates/addons/amazon_payment_services/images/visa-logo.png" height="19" />
                                </div>
                                <div class="is2or-gateway-item__name">Visa</div>
                            </label>

                            <label class="is2or-gateway-item" onclick="is2orSelectGateway('cc', 'Master Card', ['mastercard-logo.png'])">
                                <input type="radio" 
                                    name="payment_data[aps][gateway]" 
                                    value="cc"
                                    form="litecheckout_payments_form"
                                    style="display:none;" />
                                <div class="is2or-gateway-item__logos">
                                    <img src="{$config.current_location}/design/themes/responsive/templates/addons/amazon_payment_services/images/mastercard-logo.png" height="19" />
                                </div>
                                <div class="is2or-gateway-item__name">MasterCard</div>
                            </label>

                            <label class="is2or-gateway-item" onclick="is2orSelectGateway('cc', 'American Express', ['amex-logo.png'])">
                                <input type="radio" 
                                    name="payment_data[aps][gateway]" 
                                    value="cc"
                                    form="litecheckout_payments_form"
                                    style="display:none;" />
                                <div class="is2or-gateway-item__logos">
                                    <img src="{$config.current_location}/design/themes/responsive/templates/addons/amazon_payment_services/images/amex-logo.png" height="19" />
                                </div>
                                <div class="is2or-gateway-item__name">American Express</div>
                            </label>

                            <label class="is2or-gateway-item" onclick="is2orSelectGateway('cc', 'Mada', ['mada-logo.png'])">
                                <input type="radio" 
                                    name="payment_data[aps][gateway]" 
                                    value="cc"
                                    form="litecheckout_payments_form"
                                    style="display:none;" />
                                <div class="is2or-gateway-item__logos">
                                    <img src="{$config.current_location}/design/themes/responsive/templates/addons/amazon_payment_services/images/mada-logo.png" height="19" />
                                </div>
                                <div class="is2or-gateway-item__name">Mada</div>
                            </label>

                            <label class="is2or-gateway-item" onclick="is2orSelectGateway('tabby', 'Tabby', ['tabby-logo.png'])">
                                <input type="radio" 
                                    name="payment_data[aps][gateway]" 
                                    value="tabby"
                                    form="litecheckout_payments_form"
                                    style="display:none;" />
                                <div class="is2or-gateway-item__logos">
                                    <img src="{$config.current_location}/design/themes/responsive/templates/addons/amazon_payment_services/images/tabby-logo.png" height="19" />
                                </div>
                                <div class="is2or-gateway-item__name">Tabby</div>
                            </label>

                        </div>

                        <select style="display:none;"
                                data-ca-lite-checkout-field="payment_id"
                                data-ca-lite-checkout-auto-save-on-change="true"
                                form="litecheckout_payments_form"
                                name="payment_id">
                            {foreach $payment_methods as $payment}
                                <option value="{$payment.payment_id}"
                                    {if $payment.payment_id == $cart.payment_id}selected{/if}>
                                    {$payment.payment}
                                </option>
                            {/foreach}
                        </select>

                    </div>
                {/if}

                {** --- USE PROMO --- **}
                {if $cart.coupons}
                    {assign var="coupon_keys" value=$cart.coupons|@array_keys}
                    {assign var="coupon_str" value=", "|implode:$coupon_keys}
                {/if}
                <div style="border-bottom: 2px solid #c2c2c2; padding-bottom: 20px; padding-top: 20px;">
                    <span class="is2or-card__title">{__("use_promo")}</span>
                    <div class="is2or-promo-row">
                        <input type="text"
                            id="is2or_coupon_input"
                            name="coupon_code"
                            class="is2or-promo-input cm-hint"
                            placeholder="{__("insert_promotion_code")}"
                            value="{if $cart.coupons}{$coupon_str}{/if}" />
                        <button type="button"
                                id="is2or_coupon_apply_btn"
                                class="is2or-promo-apply-btn">
                            {__("apply")}
                        </button>
                        <span id="is2or_coupon_spinner" class="is2or-promo-spinner"></span>
                    </div>
                    <div id="is2or_coupon_msg" class="is2or-coupon-msg" style="display:none;"></div>
                    {** Applied promotions info **}
                    {if $cart.applied_promotions}
                        <div class="is2or-applied-promos">
                            {foreach $cart.applied_promotions as $promo}
                                <div class="is2or-applied-promo">
                                    <span class="is2or-applied-promo__name">✓ {$promo.name}</span>
                                    {if $promo.detailed_description|trim}
                                        <span class="is2or-applied-promo__desc">{$promo.detailed_description|trim nofilter}</span>
                                    {/if}
                                </div>
                            {/foreach}
                        </div>
                    {/if}
                </div>

                {** --- ORDER SUMMARY --- **}
                <div style="padding-bottom: 20px; padding-top: 20px;">
                    <span class="is2or-card__title">{__("order_summary")}</span>
                    <div class="is2or-summary-rows">

                        {** Amount **}
                        <div class="is2or-summary-row">
                            <span class="is2or-summary-row__label">
                                {__("amount")} ({$cart.amount} {if $cart.amount == 1}{__("item")}{else}{__("items")}{/if})
                            </span>
                            <span class="is2or-summary-row__value">
                                {include file="common/price.tpl" value=$cart.display_subtotal}
                            </span>
                        </div>

                        {** Shipping **}
                        {if $cart.shipping_required}
                            <div class="is2or-summary-row">
                                <span class="is2or-summary-row__label">{__("shipping")}</span>
                                <span class="is2or-summary-row__value">
                                    {if $cart.display_shipping_cost|floatval}
                                        {include file="common/price.tpl" value=$cart.display_shipping_cost}
                                    {else}
                                        {__("free")}
                                    {/if}
                                </span>
                            </div>
                        {/if}

                        {** Taxes **}
                        {if $cart.taxes}
                            {foreach from=$cart.taxes item="tax"}
                                <div class="is2or-summary-row">
                                    <span class="is2or-summary-row__label">
                                        {$tax.description}
                                        {if $tax.price_includes_tax == "Y"} ({__("included")}){/if}
                                    </span>
                                    <span class="is2or-summary-row__value">
                                        {include file="common/price.tpl" value=$tax.tax_subtotal}
                                    </span>
                                </div>
                            {/foreach}
                        {/if}

                        {** Payment surcharge **}
                        {if $cart.payment_surcharge && !$take_surcharge_from_vendor}
                            <div class="is2or-summary-row">
                                <span class="is2or-summary-row__label">
                                    {$cart.payment_surcharge_title|default:__("payment_surcharge")}
                                </span>
                                <span class="is2or-summary-row__value">
                                    {include file="common/price.tpl" value=$cart.payment_surcharge}
                                </span>
                            </div>
                        {/if}

                        {** Discount **}
                        {if $cart.subtotal_discount|floatval}
                            <div class="is2or-summary-row">
                                <span class="is2or-summary-row__label">{__("discount")}</span>
                                <span class="is2or-summary-row__value is2or-summary-row__value--discount">
                                    -{include file="common/price.tpl" value=$cart.subtotal_discount}
                                </span>
                            </div>
                        {/if}

                        {** Platform fee / extra hooks **}
                        {hook name="checkout:summary"}{/hook}

                        <div class="is2or-summary-divider" style="border-bottom: 1px solid #c2c2c2; padding-bottom: 5px;"></div>

                        {** Order Total **}
                        <div class="is2or-summary-total">
                            <span class="is2or-summary-total__label">{__("order_total")}</span>
                            <span class="is2or-summary-total__value">
                                {if $cart.payment_surcharge && !$take_surcharge_from_vendor}
                                    {$display_total = $cart.total + $cart.payment_surcharge}
                                {else}
                                    {$display_total = $cart.total}
                                {/if}
                                {include file="common/price.tpl"
                                    value=$display_total
                                    span_id="is2or_cart_total"
                                    class="ty-price"}
                            </span>
                        </div>

                    </div>
                </div>

                {** --- PLACE ORDER BUTTON --- **}
                <div>
                    <button type="submit"
                            form="litecheckout_payments_form"
                            class="is2or-place-order-btn"
                            style="border-radius:30px"
                            data-ca-lite-checkout-element="place-order-button">
                        {__("place_order")}
                    </button>
                    <div class="is2or-place-order-terms">
                        {__("by_placing_order_agree")}
                        <a href="{$config.http_location}/terms-of-use" target="_blank">{__("terms_and_conditions")}</a>
                    </div>
                </div>
            </div>
        </div>
        {** END RIGHT COLUMN **}

    </div>
    {** END is2or-checkout-wrapper **}

    
    {** ===== ADDRESS POPUP ===== **}
    {if $auth.user_id && $allow_multiple_profiles}
    <div id="is2or_address_popup" class="is2or-popup-overlay" style="display:none;" onclick="is2orCloseAddressPopupOutside(event)">
        <div class="is2or-popup">
            <div class="is2or-popup__header">
                <h2 class="is2or-popup__title">{__("change_delivery_address")}</h2>
                <button type="button" class="is2or-popup__close" onclick="is2orCloseAddressPopup()">
                    <i class="ty-icon-cancel"></i>
                </button>
            </div>
            <div class="is2or-popup__body">

                {** Add new address button **}
                <a href="{"checkout.update_profile"|fn_url}"
                class="is2or-popup__add-btn cm-dialog-opener cm-dialog-auto-size cm-dialog-destroy-on-close"
                data-ca-target-id="create_user_profile"
                data-ca-dialog-title="{__("create_profile")}">
                    {__("add_new_address")}
                </a>

                {** Profiles list **}
                <div class="is2or-address-list">
                    {foreach $user_profiles as $profile}
                        <div class="is2or-address-item {if $profile.profile_id == $cart.profile_id}is2or-address-item--selected{/if}"
                            id="is2or_profile_{$profile.profile_id}">
                            <div class="is2or-address-item__body">
                                {if $profile.profile_name}
                                    <div class="is2or-address-item__name">{$profile.profile_name}</div>
                                {/if}
                                <div class="is2or-address-item__detail">
                                    {$profile.s_address}
                                    {if $profile.s_city}, {$profile.s_city}{/if}
                                    {if $profile.s_state_descr}, {$profile.s_state_descr}{/if}
                                    {if $profile.s_zipcode} {$profile.s_zipcode}{/if}
                                    {if $profile.s_country_descr}, {$profile.s_country_descr}{/if}
                                </div>
                                {if $profile.s_firstname || $profile.s_lastname}
                                    <div class="is2or-address-item__meta">
                                        {__("recipient")}: {$profile.s_firstname} {$profile.s_lastname}
                                    </div>
                                {/if}
                                {if $profile.phone}
                                    <div class="is2or-address-item__meta">
                                        {__("phone")}: {$profile.phone}
                                    </div>
                                {/if}
                                <div class="is2or-address-item__actions">
                                    <a class="is2or-address-item__edit cm-dialog-opener cm-dialog-auto-size cm-dialog-destroy-on-close"
                                    href="{"checkout.update_profile?profile_id=`$profile.profile_id`"|fn_url}"
                                    data-ca-target-id="edit_user_profile_{$profile.profile_id}"
                                    data-ca-dialog-title="{__("editing_profile_name", ['[name]' => $profile.profile_name])}">
                                        {__("edit_address")}
                                    </a>
                                    {if $profile.profile_id != $cart.profile_id}
                                        <a class="is2or-address-item__delete cm-confirm"
                                        href="{"checkout.delete_profile?profile_id=`$profile.profile_id`"|fn_url}">
                                            {__("delete")}
                                        </a>
                                    {/if}
                                </div>
                            </div>
                            <div class="is2or-address-item__right">
                                {if $profile.profile_id == $cart.profile_id}
                                    <span class="is2or-address-item__selected-label">{__("selected")}</span>
                                {else}
                                    <button type="button"
                                            class="is2or-address-item__choose-btn"
                                            onclick="is2orSelectProfile({$profile.profile_id})">
                                        {__("choose")}
                                    </button>
                                {/if}
                            </div>
                        </div>
                    {/foreach}
                </div>

            </div>
        </div>
    </div>
    {/if}

<script>
    // ================================================================
    // PER-VENDOR SHIPPING FUNCTIONS
    // ================================================================

    /**
     * Toggle dropdown untuk vendor tertentu.
     * Menutup dropdown vendor lain yang sedang terbuka.
     */
    function is2orToggleVendorDropdown(groupKey) {
        var dropdown = document.getElementById('is2or_shipping_dropdown_' + groupKey);
        var chevron  = document.getElementById('is2or_shipping_chevron_' + groupKey);
        if (!dropdown) return;

        var isOpen = dropdown.style.display !== 'none';

        // Tutup semua dropdown vendor lain
        document.querySelectorAll('.is2or-shipping-dropdown').forEach(function(d) {
            d.style.display = 'none';
        });
        document.querySelectorAll('[id^="is2or_shipping_chevron_"]').forEach(function(c) {
            c.classList.remove('is2or-shipping-selected__chevron--open');
        });

        // Toggle dropdown ini
        if (!isOpen) {
            dropdown.style.display = 'block';
            chevron.classList.add('is2or-shipping-selected__chevron--open');
        }
    }

    /**
     * Tampilkan kategori tertentu di dalam dropdown vendor.
     * Hanya mempengaruhi elemen di dalam vendor block yang sama.
     */
    function is2orShowVendorCat(groupKey, cat) {
        var dropdownEl = document.getElementById('is2or_shipping_dropdown_' + groupKey);
        if (!dropdownEl) return;

        // Sembunyikan semua cat-list dalam vendor ini
        dropdownEl.querySelectorAll('.is2or-shipping-cat-list').forEach(function(l) {
            l.style.display = 'none';
        });

        // Non-aktifkan semua tombol dalam vendor ini
        dropdownEl.querySelectorAll('.is2or-shipping-cat-btn').forEach(function(b) {
            b.classList.remove('is2or-shipping-cat-btn--active');
        });

        // Tampilkan list yang dipilih
        var target = document.getElementById('is2or_vcat_' + groupKey + '_' + cat);
        if (target) target.style.display = 'flex';

        // Aktifkan tombol yang diklik
        var activeBtn = dropdownEl.querySelector('[onclick="is2orShowVendorCat(\'' + groupKey + '\', \'' + cat + '\')"]');
        if (activeBtn) activeBtn.classList.add('is2or-shipping-cat-btn--active');
    }

    /**
     * Pilih shipping untuk vendor tertentu.
     * - Check radio button
     * - Update tampilan "selected" di header dropdown
     * - Update styling item aktif
     * - Recalculate CS-Cart shipping cost
     * - Tutup dropdown vendor ini
     * - Cek validasi semua vendor
     */
    function is2orSelectShipping(shippingId, groupKey) {
        var radio = document.getElementById('sh_' + groupKey + '_' + shippingId);
        if (radio) {
            radio.checked = true;
            radio.dispatchEvent(new Event('change', { bubbles: true }));
            fn_calculate_total_shipping_cost();
        }

        // Update active class pada shipping item di vendor ini
        var dropdown = document.getElementById('is2or_shipping_dropdown_' + groupKey);
        if (dropdown) {
            dropdown.querySelectorAll('.is2or-shipping-item').forEach(function(item) {
                item.classList.remove('is2or-shipping-item--active');
            });
            var activeLabel = dropdown.querySelector('label[for="sh_' + groupKey + '_' + shippingId + '"], label:has(#sh_' + groupKey + '_' + shippingId + ')');
            // Fallback: cari via radio parent
            if (!activeLabel && radio) {
                activeLabel = radio.closest('.is2or-shipping-item');
            }
            if (activeLabel) activeLabel.classList.add('is2or-shipping-item--active');
        }

        // Update teks display di header selected
        var nameEl = document.getElementById('is2or_vship_name_' + groupKey);
        var etaEl  = document.getElementById('is2or_vship_eta_' + groupKey);
        if (nameEl && radio) {
            var itemEl = radio.closest('.is2or-shipping-item');
            if (itemEl) {
                var itemNameEl  = itemEl.querySelector('.is2or-shipping-item__name');
                var itemEtaEl   = itemEl.querySelector('.is2or-shipping-item__eta');
                var itemPriceEl = itemEl.querySelector('.is2or-shipping-item__price');

                var shippingName  = itemNameEl  ? itemNameEl.textContent.trim()  : '';
                var shippingPrice = itemPriceEl ? itemPriceEl.textContent.trim() : '';

                nameEl.textContent = shippingName + (shippingPrice ? ' (' + shippingPrice + ')' : '');
                nameEl.classList.remove('is2or-shipping-selected__name--empty');

                if (etaEl) {
                    etaEl.textContent = itemEtaEl ? itemEtaEl.textContent.trim() : '';
                }
            }
        }

        // Tutup dropdown setelah memilih
        setTimeout(function() {
            var dd = document.getElementById('is2or_shipping_dropdown_' + groupKey);
            var ch = document.getElementById('is2or_shipping_chevron_' + groupKey);
            if (dd) dd.style.display = 'none';
            if (ch) ch.classList.remove('is2or-shipping-selected__chevron--open');
        }, 250);

        // Validasi semua vendor sudah dipilih
        is2orValidateAllVendorShipping();
    }

    /**
     * Validasi bahwa setiap vendor sudah memiliki shipping yang dipilih.
     * Jika ada yang belum, tampilkan indikator error dan disable Place Order button.
     */
    function is2orValidateAllVendorShipping() {
        var vendorBlocks = document.querySelectorAll('.is2or-vendor-shipping');
        var allSelected = true;

        vendorBlocks.forEach(function(block) {
            var groupKey = block.getAttribute('data-group-key');
            var radios   = block.querySelectorAll('input[type="radio"][name="shipping_ids[' + groupKey + ']"]');
            var isChecked = false;
            radios.forEach(function(r) { if (r.checked) isChecked = true; });

            var selectedEl = document.getElementById('is2or_shipping_selected_' + groupKey);
            if (isChecked) {
                if (selectedEl) selectedEl.classList.remove('is2or-shipping-selected--error');
            } else {
                if (selectedEl) selectedEl.classList.add('is2or-shipping-selected--error');
                allSelected = false;
            }
        });

        // Enable / disable Place Order button
        var placeOrderBtn = document.querySelector('[data-ca-lite-checkout-element="place-order-button"]');
        if (placeOrderBtn) {
            if (allSelected) {
                placeOrderBtn.disabled = false;
                placeOrderBtn.classList.remove('is2or-place-order-btn--disabled');
            } else {
                placeOrderBtn.disabled = true;
                placeOrderBtn.classList.add('is2or-place-order-btn--disabled');
            }
        }

        return allSelected;
    }

    /**
     * Intercept Place Order submit — blokir jika ada vendor yang belum pilih shipping.
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Jalankan validasi awal saat halaman load
        is2orValidateAllVendorShipping();

        var placeOrderBtn = document.querySelector('[data-ca-lite-checkout-element="place-order-button"]');
        if (placeOrderBtn) {
            placeOrderBtn.addEventListener('click', function(e) {
                if (!is2orValidateAllVendorShipping()) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    // Scroll ke vendor pertama yang belum pilih shipping
                    var errorEl = document.querySelector('.is2or-shipping-selected--error');
                    if (errorEl) {
                        errorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    return false;
                }
            }, true); // capture phase agar jalan sebelum CS-Cart handler
        }
    });

    function is2orOpenAddressPopup() {
        document.getElementById('is2or_address_popup').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function is2orCloseAddressPopup() {
        document.getElementById('is2or_address_popup').style.display = 'none';
        document.body.style.overflow = '';
    }

    function is2orCloseAddressPopupOutside(event) {
        if (event.target === document.getElementById('is2or_address_popup')) {
            is2orCloseAddressPopup();
        }
    }

    function is2orSelectProfile(profileId) {
        // Submit form to select profile via CS-Cart mechanism
        var form = document.createElement('form');
        form.method = 'post';
        form.action = '{$config.current_url}';

        var fields = {
            'dispatch': 'checkout.checkout',
            'profile_id': profileId,
            'result_ids': 'checkout*,cart_status*'
        };

        for (var key in fields) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = fields[key];
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('submit', function(e) {
            var form = e.target;
            if (form.id === 'is2or_profile_form') {
                var billingWrapper = form.querySelector('.billing_address_fields');
                if (billingWrapper) {
                    var hiddenParent = billingWrapper.closest('.hidden');
                    if (hiddenParent) {
                        var requiredFields = hiddenParent.querySelectorAll('.cm-required, .cm-failed-field, .cm-failed-label');
                        requiredFields.forEach(function(el) {
                            el.classList.remove('cm-required');
                            el.classList.remove('cm-failed-field');
                            el.classList.remove('cm-failed-label');
                        });
                        var errorSpans = hiddenParent.querySelectorAll('.help-inline');
                        errorSpans.forEach(function(el) { el.remove(); });
                        var errorDivs = hiddenParent.querySelectorAll('.error');
                        errorDivs.forEach(function(el) { el.classList.remove('error'); });
                    }
                }
            }
        }, true); // true = capture phase, runs BEFORE CS-Cart validation

        // Auto-select shipping pertama untuk setiap vendor yang belum dipilih
        document.querySelectorAll('.is2or-vendor-shipping').forEach(function(block) {
            var groupKey = block.getAttribute('data-group-key');

            // Cek apakah vendor ini sudah ada yang ter-check
            var alreadyChecked = block.querySelector('input[type="radio"]:checked');
            if (alreadyChecked) return; // sudah ada pilihan, skip

            // Cari radio pertama dari default cat (regular → sameday → instant → bulky)
            var cats = ['regular', 'sameday', 'instant', 'bulky'];
            var firstRadio = null;
            for (var i = 0; i < cats.length; i++) {
                var catEl = document.getElementById('is2or_vcat_' + groupKey + '_' + cats[i]);
                if (catEl) {
                    firstRadio = catEl.querySelector('input[type="radio"]');
                    if (firstRadio) break;
                }
            }

            if (!firstRadio) return;

            firstRadio.checked = true;
            firstRadio.dispatchEvent(new Event('change', { bubbles: true }));

            // Update display header untuk vendor ini
            var label = firstRadio.closest('.is2or-shipping-item');
            if (label) {
                label.classList.add('is2or-shipping-item--active');

                var nameEl  = document.getElementById('is2or_vship_name_' + groupKey);
                var etaEl   = document.getElementById('is2or_vship_eta_' + groupKey);
                var itemNameEl  = label.querySelector('.is2or-shipping-item__name');
                var itemEtaEl   = label.querySelector('.is2or-shipping-item__eta');
                var itemPriceEl = label.querySelector('.is2or-shipping-item__price');

                if (nameEl && itemNameEl) {
                    var price = itemPriceEl ? itemPriceEl.textContent.trim() : '';
                    nameEl.textContent = itemNameEl.textContent.trim() + (price ? ' (' + price + ')' : '');
                    nameEl.classList.remove('is2or-shipping-selected__name--empty');
                }
                if (etaEl) {
                    etaEl.textContent = itemEtaEl ? itemEtaEl.textContent.trim() : '';
                }
            }
        });

        // Validasi awal setelah auto-select
        is2orValidateAllVendorShipping();

        var lcForm = document.getElementById('litecheckout_payments_form');
        if (lcForm) {
            lcForm.addEventListener('submit', function() {
                // Inject security_hash
                var existingHash = document.querySelector('input[name="security_hash"]');
                if (existingHash && existingHash.value) {
                    var hashInput = lcForm.querySelector('input[name="security_hash"]');
                    if (!hashInput) {
                        hashInput = document.createElement('input');
                        hashInput.type = 'hidden';
                        hashInput.name = 'security_hash';
                        hashInput.className = 'cm-no-hide-input';
                        lcForm.appendChild(hashInput);
                    }
                    hashInput.value = existingHash.value;
                }

                var dispatchInput = lcForm.querySelector('input[name="dispatch"]');
                if (!dispatchInput) {
                    dispatchInput = document.createElement('input');
                    dispatchInput.type = 'hidden';
                    dispatchInput.name = 'dispatch';
                    lcForm.appendChild(dispatchInput);
                }
                dispatchInput.value = 'checkout.place_order';
            });
        }
    });

    function is2orToggleGatewayDropdown() {
        var dropdown = document.getElementById('is2or_gateway_dropdown');
        var chevron = document.getElementById('is2or_gateway_chevron');
        if (dropdown.style.display === 'none') {
            dropdown.style.display = 'block';
            chevron.classList.add('is2or-gateway-selected__chevron--open');
        } else {
            dropdown.style.display = 'none';
            chevron.classList.remove('is2or-gateway-selected__chevron--open');
        }
    }

    function is2orSelectGateway(gatewayType, displayName, logos) {
        var baseUrl = '{$config.current_location}/design/themes/responsive/templates/addons/amazon_payment_services/images/';
        
        // Update selected display
        var nameEl = document.getElementById('is2or_gateway_name');
        var logosEl = document.getElementById('is2or_gateway_logos');
        
        if (nameEl) nameEl.textContent = displayName;
        
        if (logosEl) {
            logosEl.innerHTML = '';
            logos.forEach(function(logo) {
                var img = document.createElement('img');
                img.src = baseUrl + logo;
                img.height = 22;
                logosEl.appendChild(img);
            });
        }

        // Check the correct radio button
        var radios = document.querySelectorAll('input[name="payment_data[aps][gateway]"]');
        radios.forEach(function(r) {
            if (r.value === gatewayType) {
                r.checked = true;
            }
        });

        // Update active state
        document.querySelectorAll('.is2or-gateway-item').forEach(function(item) {
            item.classList.remove('is2or-gateway-item--active');
        });
        event.currentTarget.classList.add('is2or-gateway-item--active');

        // Close dropdown
        document.getElementById('is2or_gateway_dropdown').style.display = 'none';
        document.getElementById('is2or_gateway_chevron').classList.remove('is2or-gateway-selected__chevron--open');
    }

    document.addEventListener('DOMContentLoaded', function() {
        var hiddenTotalEl  = document.getElementById('cart_total');
        var visibleTotalEl = document.getElementById('is2or_cart_total');

        if (hiddenTotalEl && visibleTotalEl) {
            var syncTotal = function() {
                visibleTotalEl.innerHTML = hiddenTotalEl.innerHTML;
            };

            var observer = new MutationObserver(syncTotal);
            observer.observe(hiddenTotalEl, { childList: true, characterData: true, subtree: true });
        }
    });

    // ================================================================
    // COUPON / PROMO CODE LOGIC
    // ================================================================

    (function() {
        var _lastSubmittedCode = null;
        var _isLoading = false;

        function is2orShowMsg(type, text) {
            var msgEl = document.getElementById('is2or_coupon_msg');
            if (!msgEl) return;
            msgEl.className = 'is2or-coupon-msg is2or-coupon-msg--' + type;
            msgEl.textContent = text;
            msgEl.style.display = 'block';

            var promosEl = document.querySelector('.is2or-applied-promos');
            if (promosEl) {
                promosEl.style.display = type === 'error' ? 'none' : 'block';
            }
        }

        function is2orHideMsg() {
            var msgEl = document.getElementById('is2or_coupon_msg');
            if (msgEl) msgEl.style.display = 'none';

            var promosEl = document.querySelector('.is2or-applied-promos');
            if (promosEl) promosEl.style.display = 'block';
        }

        function is2orSetBtnLoading(loading) {
            var btn     = document.getElementById('is2or_coupon_apply_btn');
            var spinner = document.getElementById('is2or_coupon_spinner');
            if (!btn) return;
            btn.disabled = loading;
            if (spinner) {
                if (loading) {
                    spinner.classList.add('is2or-promo-spinner--visible');
                } else {
                    spinner.classList.remove('is2or-promo-spinner--visible');
                }
            }
        }

        function is2orGetSecurityHash() {
            var el = document.querySelector('input[name="security_hash"]');
            return el ? el.value : '';
        }

        function is2orSubmitCoupon() {
            var input = document.getElementById('is2or_coupon_input');
            if (!input || _isLoading) return;

            var code = input.value.trim();
            if (code === _lastSubmittedCode) return;

            is2orHideMsg();
            _isLoading = true;
            _lastSubmittedCode = code;
            is2orSetBtnLoading(true);

            $.ajax({
                url: '{$config.current_location}/',
                method: 'POST',
                dataType: 'json',
                data: {
                    dispatch: 'checkout.apply_coupon',
                    coupon_code: code,
                    result_ids: 'checkout*,cart_status*,cart_items,payment-methods,litecheckout_form',
                    redirect_url: 'index.php?dispatch=checkout.cart',
                    security_hash: is2orGetSecurityHash(),
                    full_render: 'Y',
                    is_ajax: '1'
                },
                success: function(response) {
                    if (!response) {
                        _lastSubmittedCode = null;
                        is2orShowMsg('error', '✗ Failed to apply promo code.');
                        return;
                    }

                    var isSuccess = false;
                    var errorMsg  = '';

                    if (response.notifications) {
                        $.each(response.notifications, function(key, notif) {
                            if (notif.extra === 'text_applied_promotions') {
                                isSuccess = true;
                                return false; // break
                            }
                            if (notif.type === 'W' || notif.type === 'E') {
                                errorMsg = notif.message || '';
                            }
                        });
                    }

                    if (isSuccess) {
                        if (response.html && response.html.checkout_totals) {
                            var $totals = $(response.html.checkout_totals);

                            var $discountValueEl = $totals.find('.ut2-cart-statistic__item_subtotal-discount .ut2-cart-statistic__value');
                            if ($discountValueEl.length) {
                                var discountNum = $discountValueEl.find('span[id], span:not([class])').last().text().trim();

                                var $existingPrice = $('#cart_total').closest('.ty-price, span').parent().clone();

                                var $discountRow = $('.is2or-summary-row__value--discount').closest('.is2or-summary-row');
                                if ($discountRow.length) {
                                    $discountRow.find('.is2or-summary-row__value--discount #cart_total, .is2or-summary-row__value--discount span[id]').text(discountNum);
                                } else {
                                    var $subtotalPrice = $('.is2or-summary-row__value').first().html();
                                    var $tempDiv = $('<div>').html($subtotalPrice);
                                    $tempDiv.find('span').last().text(discountNum);

                                    var newRow = '<div class="is2or-summary-row">' +
                                        '<span class="is2or-summary-row__label">{__("discount")}</span>' +
                                        '<span class="is2or-summary-row__value is2or-summary-row__value--discount">-' + $tempDiv.html() + '</span>' +
                                        '</div>';
                                    $('.is2or-summary-divider').before(newRow);
                                }
                            }

                            // Update order total
                            var newTotalNum = $totals.find('#cart_total').text().trim();
                            if (newTotalNum) {
                                $('#is2or_cart_total').text(newTotalNum);
                            }

                            // Update shipping
                            var $newShippingEl = $totals.find('.ut2-cart-statistic__item_shipping-method .ut2-cart-statistic__value');
                            if ($newShippingEl.length) {
                                var shippingNum = $newShippingEl.find('span[id], span:not([class])').last().text().trim();
                                $('.is2or-summary-row').filter(function() {
                                    return $(this).find('.is2or-summary-row__label').text().trim().toLowerCase() === 'shipping';
                                }).find('.is2or-summary-row__value span').last().text(shippingNum);
                            }

                            // Update subtotal
                            var $newSubtotalEl = $totals.find('.ut2-cart-statistic_subtotal .ut2-cart-statistic__value');
                            if ($newSubtotalEl.length) {
                                var subtotalNum = $newSubtotalEl.find('span[id], span:not([class])').last().text().trim();
                                $('.is2or-summary-row__value').first().find('span').last().text(subtotalNum);
                            }
                        }

                        is2orShowMsg('success', '✓ Promo code applied successfully!');

                    } else {
                        _lastSubmittedCode = null;
                        var displayMsg = errorMsg
                            ? errorMsg
                            : 'Invalid or expired promo code.';
                        is2orShowMsg('error', displayMsg);
                    }
                },
                error: function() {
                    _lastSubmittedCode = null;
                    is2orShowMsg('error', 'Failed to apply promo code. Please try again.');
                },
                complete: function() {
                    _isLoading = false;
                    is2orSetBtnLoading(false);
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            var input = document.getElementById('is2or_coupon_input');
            var btn   = document.getElementById('is2or_coupon_apply_btn');

            if (!input || !btn) return;

            if (input.value.trim() !== '') {
                _lastSubmittedCode = input.value.trim();
                var discountRow = document.querySelector('.is2or-summary-row__value--discount');
                if (discountRow) {
                    is2orShowMsg('success', '✓ Promo code applied successfully!');
                }
            }

            btn.addEventListener('click', function() {
                is2orSubmitCoupon();
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    is2orSubmitCoupon();
                }
            });

            input.addEventListener('input', function() {
                if (input.value.trim() === '') {
                    _lastSubmittedCode = null;
                    is2orHideMsg();
                    // Hapus baris discount jika input dikosongkan
                    $('.is2or-summary-row__value--discount').closest('.is2or-summary-row').remove();
                }
            });
        });
    })();
    
</script>

<script src="{$config.current_location}/js/tygh/checkout.js"></script>
<script src="{$config.current_location}/js/tygh/checkout/lite_checkout.js"></script>
<script src="{$config.current_location}/js/tygh/checkout/pickup_selector.js"></script>
<script src="{$config.current_location}/js/tygh/checkout/pickup_search.js"></script>
<script src="{$config.current_location}/js/tygh/search_pickup_points.js"></script>

<input type="hidden" 
       name="security_hash" 
       class="cm-no-hide-input" 
       value="{""|fn_generate_security_hash}"
       form="litecheckout_payments_form" />
       
<!--litecheckout_form--></div>
