{include file="views/profiles/components/profiles_scripts.tpl"}

{$dispatch = "address_selector.update"}
{$back_url = "address_selector.manage"|fn_url}

{if $settings.Checkout.address_position == "billing_first"}
    {$first_section = "B"}
    {$first_section_text = __("billing_address")}
    {$sec_section = "S"}
    {$sec_section_text = __("shipping_address")}
    {$body_id = "sa"}
{else}
    {$first_section = "S"}
    {$first_section_text = __("shipping_address")}
    {$sec_section = "B"}
    {$sec_section_text = __("billing_address")}
    {$body_id = "ba"}
{/if}

<div class="ty-account is2or-address-selector is2or-address-selector-form">
    <form name="address_selector_form" enctype="multipart/form-data" action="{""|fn_url}" method="post">
        <input type="hidden" name="return_url" value="{$back_url}" />
        <input type="hidden" name="user_data[profile_id]" value="{$user_data.profile_id|default:0}" />

        <div class="ty-control-group">
            <label class="ty-control-group__title cm-required" for="elm_profile_name">{__("is2or_address_selector.address_label")}</label>
            <input
                type="text"
                id="elm_profile_name"
                name="user_data[profile_name]"
                value="{$user_data.profile_name}"
                class="ty-input-text"
                size="32"
            />
        </div>

        {include file="views/profiles/components/profile_fields.tpl" section=$first_section body_id="" ship_to_another=true title=$first_section_text}
        {include file="views/profiles/components/profile_fields.tpl" section=$sec_section body_id=$body_id ship_to_another=true title=$sec_section_text address_flag=$profile_fields|fn_compare_shipping_billing ship_to_another=$ship_to_another}

        <div class="buttons-container">
            {include file="buttons/save.tpl" but_name="dispatch[{$dispatch}]" but_id="save_address_selector_but"}
            <a class="ty-btn ty-btn__tertiary" href="{$back_url}">{__("is2or_address_selector.back_to_addresses")}</a>
        </div>
    </form>
</div>

{capture name="mainbox_title"}{if $is_new}{__("is2or_address_selector.add_address")}{else}{__("is2or_address_selector.edit_address")}{/if}{/capture}
