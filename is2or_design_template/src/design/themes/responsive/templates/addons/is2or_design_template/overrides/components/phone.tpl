{if $name}{strip}
    {*
    Example
    ---
    {include file="components/phone.tpl"
        id="example_id"
        name="example_name"
        value=$example_value
    }
    *}
    {$rnd = rand()}
    {$is_phone_number_with_country_selection = ($settings.Appearance.phone_validation_mode === "phone_number_with_country_selection")}

    {* Checkout style *}
    {$checkout = $checkout|default:false}
    {if $checkout}
        {$id = $id|default:$rnd}
        {$show_controls = $show_controls|default:false}
        {$default_style = $default_style|default:false}
        {$required = $required|default:false}
        {$control_group_class = "litecheckout__field `$control_group_class`"|default:"litecheckout__field"}
        {$label_position = $label_position|default:"after"}
        {$label_class = "litecheckout__label `$label_class`"|default:"litecheckout__label"}
        {$label_text = $label_text|default:__("phone_mask_label")}
        {$class = "litecheckout__input litecheckout__input--mask-phone `$class`"}
        {$btn_group = "ty-btn-group--litecheckout `$btn_group`"}
        {$button_class = "ty-select-block__a--litecheckout `$button_class`"}
        {$input_phone_container_class = ($is_phone_number_with_country_selection)
            ? "ty-input-append--litecheckout `$input_phone_container_class`"
            : $input_phone_container_class
        }
        {$placeholder = ($placeholder) ? $placeholder : " "}
    {/if}

    {* Import *}
    {$id = $id|default:$rnd}
    {$phone_mask_type_class = "ty-input-phone--`$settings.Appearance.phone_validation_mode`"|replace:"_":"-"}
    {$show_control_group = $show_control_group|default:true}
    {$show_controls = $show_controls|default:true}
    {$required = $required|default:false}
    {$name = $name|default:""}
    {$value = $value|default:""}
    {$title = $title|default:__("phone_mask_label")}
    {$class = $class|default:""}
    {$attrs = $attrs|default:false}
    {$extra = $extra|default:""}
    {$control_group_class = $control_group_class|default:""}
    {$label_position = $label_position|default:"default"}
    {$label_class = $label_class|default:""}
    {$label_text = $label_text|default:__("phone_mask_label")}
    {$input_phone_container_class = $input_phone_container_class|default:""}
    {$btn_group = $btn_group|default:""}
    {$button_class = $button_class|default:""}
    {$default_style = $default_style|default:true}
    {$placeholder = $placeholder}
    {$autocomplete = $autocomplete}
    {$aria_label = $aria_label|default:__("phone_mask_label")}
    {$attrs_string = $attrs_string|default:false}
    {$show_colon = $show_colon|default:false}
    {$field_name_helper = $field_name_helper|default:$name}
    {if $width === "full"}
        {* Empty *}
    {elseif $width === "large"}
        {$input_phone_container_class = "`$input_phone_container_class` ty-input-phone-container--large"}
    {else}
        {$input_phone_container_class = "`$input_phone_container_class` ty-input-phone-container--default"}
    {/if}

    {* Detect RTL language *}
    {$is_rtl = ($smarty.const.CART_LANGUAGE === "ar" || $smarty.const.CART_LANGUAGE === "he" || $smarty.const.CART_LANGUAGE === "fa")}
    {$dropdown_direction_class = ($is_rtl) ? "ty-dropright" : "ty-dropleft"}
    {$phone_rtl_class = ($is_rtl) ? "cm-mask-phone--rtl" : ""}

    {* Get countries list *}
    {$country_code = $profile_data.s_country|default:$settings.Checkout.default_country}
    {$countries = 1|fn_get_simple_phone_country_codes}
    {foreach $countries as $id => $country}
        {$countries[$id].symbol = "+`$country.phone_code`"}
    {/foreach}
    {* Set country code by phone *}
    {foreach $countries as $id => $country}
        {if $value|substr:0:($country.symbol|strlen) === $country.symbol}
            {$country_code = $country.country_code}
            {if $country.is_main_for_phone_code}
                {break}
            {/if}
        {/if}
    {/foreach}
    {if $placeholder !== false}
        {$placeholder = ($placeholder || $placeholder === "") ? $placeholder : $countries[$country_code|lower].symbol}
    {/if}
    {capture name="label"}
        <label for="{$id}" class="cm-mask-phone-label {if $default_style}ty-control-group__title{/if} {if $required}cm-required cm-trim{/if} {$label_class}">
            {$label_text}{if $show_colon}:{/if}
        </label>
    {/capture}

    {if $show_control_group}
    <div class="cm-mask-phone-group {if $default_style}ty-control-group{/if} {$control_group_class}" data-ca-phone-mask-group-id="{$id}">
        {if $label_position === "default"}
            {$smarty.capture.label nofilter}
        {/if}
    {/if}

    {if $show_controls}
        <div class="controls">
    {/if}
    <div class="cm-field-container ty-field-phone-container {if $is_phone_number_with_country_selection}ty-input-append ty-input-append--phone-mask{/if} {$input_phone_container_class}" style="position: relative;">
        <style>
            .ty-select-block__a--phone-mask.ty-select-block__a {
                background: initial !important;
                border-radius: initial !important;
                border: 0 none !important;
                border-inline-end: 1px solid var(--color-base5) !important;
                border-inline-start: 0 none !important;
                padding: initial !important;
                min-height: initial !important;
                height: initial !important;
                inline-size: 50px !important;
                position: absolute !important;
                z-index: 2 !important;
                inset-block: 1px !important;
                inset-inline-start: 1px !important;
                inset-inline-end: auto !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            .ty-select-block__a--phone-mask.ty-select-block__a[class*="checkout"] {
                border-color: var(--color-base5) !important;
            }
            .ty-select-block__a--phone-mask.ty-select-block__a.open .ty-select-block__arrow {
                color: var(--color-font-lighten) !important;
            }
            .ty-phone-code-prefix {
                position: absolute !important;
                inset-inline-start: 51px !important;
                top: 1px !important;
                bottom: 1px !important;
                width: 60px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                background: #fff !important;
                color: var(--color-font-lighten, #555) !important;
                font-size: 14px !important;
                border-inline-end: 1px solid var(--color-base5) !important;
                z-index: 2 !important;
                pointer-events: none !important;
            }
            .cm-mask-phone.ty-input-phone {
                inset-inline-start: 51px !important;
                padding-left: 120px !important;
            }
            .cm-mask-phone.ty-input-phone.cm-mask-phone--rtl {
                padding-inline-start: 0 !important;
                padding-right: 120px !important;
            }
        </style>
        {if $is_phone_number_with_country_selection}
            {include file="common/select_object.tpl"
                style="graphic"
                show_button_text=false
                show_button_symbol=false
                link_tpl=""
                suffix="phone_code_`$id`"
                items=$countries
                selected_id=$country_code|lower
                display_icons=true
                key_name="name"
                dropdown_menu_item_link_class="cm-phone-number-with-country-selection-li-link"
                assign="select_countries"
                class="cm-phone-number-with-country-selection-btn-group ty-btn-group--phone-mask `$dropdown_direction_class` `$btn_group`"
                button_class="ty-select-block__a--phone-mask `$button_class`"
                show_group=true
            }
            {$select_countries|strip|replace:"> <":"><" nofilter}
            
            <div id="prefix_{$id}" class="ty-phone-code-prefix">
                {$countries[$country_code|lower].symbol}
            </div>
        {/if}

        <input {""}
            type="tel" {""}
            id="{$id}" {""}
            name="{$name}" {""}
            value="{$value}" {""}
            class="cm-mask-phone ty-input-phone ty-input-fill {$phone_mask_type_class} {if $default_style}ty-input-text{/if} {$phone_rtl_class} {$class}" {""}
            title="{$title}" {""}
            placeholder="" {""}
            autocomplete="off" {""}
            aria-label="{$aria_label}" {""}
            inputmode="tel" {""}
            {if $is_rtl}
                dir="ltr" {""}
                style="text-align: right;" {""}
            {/if}
            onkeypress="return /[\d\s\+\-\(\)\.#\*]/.test(event.key)" {""}
            onpaste="var t=(event.clipboardData||window.clipboardData).getData('text');if(!/^[\d\s\+\-\(\)\.#\*]+$/.test(t)){ldelim}event.preventDefault();{rdelim}" {""}
            data-ca-phone-mask-type="{$settings.Appearance.phone_validation_mode}" {""}
            data-ca-phone-mask-country="{$country_code}" {""}
            data-ca-phone-mask-symbol="{$countries[$country_code|lower].symbol}" {""}
            data-ca-check-filter="[data-ca-phone-mask-group-id='{$id}']" {""}
            data-ca-phone-mask-group-id="{$id}" {""}
            data-ca-lite-checkout-field="{$field_name_helper}" {""}
            data-ca-lite-checkout-auto-save="true" {""}
            {if $attrs}
                {$attrs|render_tag_attrs nofilter} {""}
            {/if}
            {if $attrs_string}
                {$attrs_string nofilter}
            {/if}
        />
        {if $label_position === "after"}
            {$smarty.capture.label nofilter}
        {/if}
        {$extra nofilter}
    </div>
    {if $show_controls}
        </div>
    {/if}
    {if $show_control_group}
    </div>
    {/if}
{/strip}{/if}