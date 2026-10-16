<div class="ty-vc-profile-layout">
    <link type="text/css" rel="stylesheet" href="{"design/themes/abt__unitheme2/css/addons/is2or_account_menu/style.css"|fn_url}" />

    <div class="ty-vc-profile-layout__menu">
        {include file="addons/is2or_account_menu/components/account_menu.tpl"}
    </div>

    <div class="ty-vc-profile-layout__content">
        {$tmpl='products_scroller'}
        {$continue_url = $continue_url|default:""}

        {$show_list_discount = $settings.abt__ut2.product_list.show_you_save[$settings.ab__device] !== "none"}

        {if $settings.abt__ut2.product_list.$tmpl.show_quick_view_button[$settings.ab__device] === "YesNo::YES"|enum}
            {$hide_quick_view_button = false}
        {else}
            {$hide_quick_view_button = true}
        {/if}

        {$max_key_features = 10}

        <div class="ty-compare-cards">

            <div class="ty-compare-cards__header">
                <h1 class="ty-compare-cards__title">{__("comparison_list")}</h1>
                <p class="ty-compare-cards__subtitle">{__("comparison_list_subtitle")}</p>
            </div>

            {if !$comparison_data}
                <p class="ty-no-items ty-compare__no-items">{__("no_products_selected")}</p>
                <div class="buttons-container ty-compare__button-empty">
                    {include file="buttons/continue_shopping.tpl" but_href=$continue_url|fn_url but_role="text"}
                </div>
            {else}
                {script src="js/tygh/exceptions.js"}
                {assign var="return_current_url" value=$config.current_url|escape:url}

                <div class="ty-compare-cards__grid">

                    {foreach from=$comparison_data.products item=product name=products_loop}
                        {$compare_product_id = $product.product_id}

                        <div class="ty-compare-cards__card">

                            <div class="ty-compare-cards__card-head">
                                {if $product.company_id}
                                    <a href="{"companies.view?company_id=`$product.company_id`"|fn_url}" class="ty-compare-cards__card-label ty-compare-cards__vendor-link">
                                        {$product.company_name|default:$product.company}
                                    </a>
                                {else}
                                    <span class="ty-compare-cards__card-label">{$product.company_name|default:$product.company}</span>
                                {/if}
                                <a href="{"product_features.delete_product?product_id=`$compare_product_id`&redirect_url=`$return_current_url`"|fn_url}"
                                class="ty-compare-cards__remove"
                                title="{__("remove")}">
                                    {include_ext file="common/icon.tpl" class="ty-icon-cancel-circle"}
                                </a>
                            </div>

                            {hook name="product_features:product_compare"}
                            <div class="ty-compare-cards__image">
                                <a href="{"products.view?product_id=`$compare_product_id`"|fn_url}">
                                    {include file="common/image.tpl"
                                        image_width=$settings.Thumbnails.product_lists_thumbnail_width
                                        image_height=$settings.Thumbnails.product_lists_thumbnail_height
                                        obj_id=$product.product_id
                                        images=$product.main_pair
                                        no_ids=true}
                                </a>
                            </div>
                            {/hook}

                            <a class="ty-compare-cards__name" href="{"products.view?product_id=`$compare_product_id`"|fn_url}">
                                {$product.product}
                            </a>

                            <div class="ty-compare-cards__row">
                                <span class="ty-compare-cards__row-label">{__("price")}</span>
                                <span class="ty-compare-cards__row-value ty-compare-cards__price">
                                    {include file="common/price.tpl" value=$product.price}
                                </span>
                            </div>

                            {if $product.rating_value}
                            <div class="ty-compare-cards__row">
                                <span class="ty-compare-cards__row-label">{__("rating")}</span>
                                <span class="ty-compare-cards__row-value ty-compare-cards__rating">{$product.rating_value}/5</span>
                            </div>
                            {/if}

                            {if $comparison_data.product_features}
                            <div class="ty-compare-cards__features">
                                <strong class="ty-compare-cards__features-title">{__("key_features")}</strong>
                                <ul class="ty-compare-cards__features-list">
                                    {$shown_features = 0}
                                    {foreach from=$comparison_data.product_features item="group_features" key="group_id"}
                                        {foreach from=$group_features item="_feature" key=id}
                                            {if $shown_features < $max_key_features}

                                                {if $product.product_features.$id}
                                                    {assign var="feature" value=$product.product_features.$id}
                                                {else}
                                                    {assign var="feature" value=$product.product_features[$group_id].subfeatures.$id}
                                                {/if}

                                                {strip}
                                                    {if $feature.feature_type === "ProductFeatures::SINGLE_CHECKBOX"|enum}
                                                        {if $feature.value === "Y"}
                                                            <li class="ty-compare-cards__features-item">{$_feature}</li>
                                                            {$shown_features = $shown_features + 1}
                                                        {/if}
                                                    {elseif $feature.feature_type == "ProductFeatures::DATE"|enum && $feature.value_int}
                                                        <li class="ty-compare-cards__features-item">{$_feature}: {$feature.value_int|date_format:"`$settings.Appearance.date_format`"}</li>
                                                        {$shown_features = $shown_features + 1}
                                                    {elseif $feature.feature_type == "ProductFeatures::MULTIPLE_CHECKBOX"|enum && $feature.variants}
                                                        {foreach from=$feature.variants item="var"}
                                                            {if $var.selected && $shown_features < $max_key_features}
                                                                <li class="ty-compare-cards__features-item">{$var.variant}</li>
                                                                {$shown_features = $shown_features + 1}
                                                            {/if}
                                                        {/foreach}
                                                    {elseif in_array($feature.feature_type, ["ProductFeatures::TEXT_SELECTBOX"|enum, "ProductFeatures::EXTENDED"|enum, "ProductFeatures::NUMBER_SELECTBOX"|enum])}
                                                        {foreach $feature.variants as $variant}
                                                            {if $variant.selected}
                                                                <li class="ty-compare-cards__features-item">{$_feature}: {$variant.variant}</li>
                                                                {$shown_features = $shown_features + 1}
                                                                {break}
                                                            {/if}
                                                        {/foreach}
                                                    {elseif $feature.feature_type == "ProductFeatures::NUMBER_FIELD"|enum && $feature.value_int}
                                                        <li class="ty-compare-cards__features-item">{$_feature}: {$feature.value_int|floatval}</li>
                                                        {$shown_features = $shown_features + 1}
                                                    {elseif $feature.value}
                                                        <li class="ty-compare-cards__features-item">{$_feature}: {$feature.value}</li>
                                                        {$shown_features = $shown_features + 1}
                                                    {/if}
                                                {/strip}

                                            {/if}
                                        {/foreach}
                                    {/foreach}
                                </ul>
                            </div>
                            {/if}

                            <div class="ty-compare-cards__buttons">
                                {include file="blocks/list_templates/simple_list.tpl"
                                    product=$product
                                    min_qty=false
                                    show_qty=false
                                    show_add_to_cart=true
                                    show_discount_label=false
                                    show_rating_num=false
                                    show_rating=false
                                    show_name=false
                                    show_price=false
                                    hide_compare_list_button=true
                                    hide_wishlist_button=true
                                    hide_quick_view_button=true
                                    but_role="action"}
                            </div>

                        </div>
                    {/foreach}

                    {* Kartu kosong untuk menambah produk baru ke daftar perbandingan *}
                    <a class="ty-compare-cards__card ty-compare-cards__card--add" href="{$continue_url|fn_url}">
                        <span class="ty-compare-cards__add-icon">+</span>
                        <span class="ty-compare-cards__add-title">{__("add_product")}</span>
                        <span class="ty-compare-cards__add-subtitle">{__("add_product_subtitle")}</span>
                    </a>

                </div>

                {if $comparison_data.hidden_features}
                {include file="common/subheader.tpl" title=__("add_feature")}
                <form action="{""|fn_url}" method="post" name="add_feature_form">
                    <input type="hidden" name="redirect_url" value="{$config.current_url}" />
                    <div class="ut2-comparison-product-features-add">{html_checkboxes name="add_features" options=$comparison_data.hidden_features columns="1"}</div>

                    <div class="ty-mt-m">
                        {include file="buttons/button.tpl" but_text=__("add") but_name="dispatch[product_features.add_feature]"}
                    </div>
                </form>
                {/if}

                <div class="buttons-container ut2-compare__buttons">
                    {include file="buttons/button.tpl" but_text=__("clear_list") but_href="product_features.clear_list?redirect_url=`$return_current_url`" but_meta="ty-btn__outline"}
                    {include file="buttons/continue_shopping.tpl" but_href=$continue_url|fn_url but_role="text"}
                </div>
            {/if}

        </div>

        {capture name="mainbox_title"}{__("compare")}{/capture}
    </div>
</div>


<style>
    .ty-compare-cards__header { margin-bottom: 24px; }
    .ty-compare-cards__title { font-size: 24px; font-weight: 700; margin: 0 0 4px; }
    .ty-compare-cards__subtitle { color: #6b7280; margin: 0; }

    .ty-compare-cards__grid {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
    }

    .ty-compare-cards__card {
        width: 260px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 16px;
        display: flex;
        flex-direction: column;
        background: #fff;
    }

    .ty-compare-cards__card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .ty-compare-cards__card-label {
        font-weight: 600;
        font-size: 14px;
    }

    .ty-compare-cards__vendor-link {
        color: #111827;
        text-decoration: none;
    }

    .ty-compare-cards__vendor-link:hover {
        color: #f97316;
        text-decoration: underline;
    }

    .ty-compare-cards__remove {
        color: #9ca3af;
    }

    .ty-compare-cards__image {
        background: #f3f4f6;
        border-radius: 6px;
        aspect-ratio: 1 / 1;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
        overflow: hidden;
    }

    .ty-compare-cards__name {
        font-weight: 600;
        font-size: 15px;
        margin-bottom: 8px;
        display: block;
        color: #111827;
    }

    .ty-compare-cards__row {
        display: flex;
        justify-content: space-between;
        font-size: 14px;
        margin-bottom: 4px;
    }

    .ty-compare-cards__row-label { color: #6b7280; }
    .ty-compare-cards__price { font-weight: 700; }
    .ty-compare-cards__rating { color: #f97316; font-weight: 600; }

    .ty-compare-cards__features { margin: 12px 0; }
    .ty-compare-cards__features-title { font-size: 14px; display: block; margin-bottom: 6px; }
    .ty-compare-cards__features-list { list-style: disc; padding-left: 18px; margin: 0; font-size: 13px; color: #374151; }
    .ty-compare-cards__features-item { margin-bottom: 2px; }

    .ty-compare-cards__buttons { margin-top: auto; }
    .ty-compare-cards__buttons .ty-btn__add-to-cart,
    .ty-compare-cards__buttons button {
        width: 100%;
    }

    .ty-compare-cards__card--add {
        width: 260px;
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #6b7280;
        text-decoration: none;
        min-height: 260px;
    }

    .ty-compare-cards__add-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 1px solid #d1d5db;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-bottom: 10px;
    }

    .ty-compare-cards__add-title { font-weight: 600; color: #111827; margin-bottom: 4px; }
    .ty-compare-cards__add-subtitle { font-size: 13px; }
    
    .ty-compare-cards__buttons .ut2-simple-list__control.icon_button .ty-btn__add-to-cart {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: auto;
        padding: 0 20px;
        height: 44px;
        border-radius: 999px;
    }

    .ty-compare-cards__buttons .ut2-simple-list__control.icon_button .ty-btn__add-to-cart span::after {
        content: "Add to cart";
        color: #fff;
        font-weight: 600;
        font-size: 14px;
        white-space: nowrap;
    }
    
    .ut2-simple-list__control.icon_button [id^="add_to_cart_update_"] .ty-btn__add-to-cart span.ty-icon::after {
        content: none !important;
    }

    .ut2-simple-list__control.icon_button [id^="add_to_cart_update_"] .ty-btn__add-to-cart span.ty-icon::after {
        content: "Add to cart" !important;
        font-family: "OutfitRegular", sans-serif !important;
        color: #fff !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        white-space: nowrap !important;
        margin-left: 6px !important;
        display: inline-block !important;
    }
</style>
