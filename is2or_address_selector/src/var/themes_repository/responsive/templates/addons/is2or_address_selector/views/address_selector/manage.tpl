<div class="ty-account is2or-address-selector is2or-address-selector-page">
    <div class="is2or-address-selector__toolbar">
        <div>
            <p class="is2or-address-selector__lead">{__("is2or_address_selector.manage_your_addresses")}</p>
        </div>
        <a class="ty-btn ty-btn__primary" href="{"address_selector.update"|fn_url}">{__("is2or_address_selector.add_address")}</a>
    </div>

    {include
        file="addons/is2or_address_selector/views/address_selector/components/address_cards.tpl"
        profiles=$profiles
        active_profile_id=$active_profile_id
        selection_mode="link"
        show_select=true
        show_edit=true
        show_delete=true
        return_url=$config.current_url
    }
</div>

{capture name="mainbox_title"}{__("is2or_address_selector.manage_addresses")}{/capture}
