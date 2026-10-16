{if $auth.user_id}
    <div class="is2or-address-selector is2or-address-selector__popup">
        <div class="is2or-address-selector__toolbar">
            <div>
                <h4 class="is2or-address-selector__title">{__("is2or_address_selector.saved_addresses")}</h4>
                <p class="is2or-address-selector__lead">{__("is2or_address_selector.manage_your_addresses")}</p>
            </div>
            <a class="ty-btn ty-btn__secondary" href="{"address_selector.manage"|fn_url}">{__("is2or_address_selector.manage_from_account")}</a>
        </div>

        {include
            file="addons/is2or_address_selector/views/address_selector/components/address_cards.tpl"
            profiles=$address_selector_profiles
            active_profile_id=$address_selector_active_profile_id
            selection_mode="ajax"
            show_select=true
            show_edit=true
            show_delete=false
            return_url=$config.current_url
        }
    </div>
{/if}

{if $locations}
    <ul class="ty-store-locator__geolocation__locations">
        {foreach $locations as $country_id => $country}
            <li class="ty-store-locator__geolocation__location__country">
                <h3 class="ty-store-locator__geolocation__location__country__title">{$country.title}</h3>
                <ul class="ty-store-locator__geolocation__location__states">
                    {foreach $country.states as $state_id => $state}
                        <li class="ty-store-locator__geolocation__location__state">
                            <h4 class="ty-store-locator__geolocation__location__state__title">{$state.title}</h4>
                            <ul class="ty-store-locator__geolocation__location__cities">
                                {foreach $state.cities as $city}
                                    <li class="ty-store-locator__geolocation__location__city">
                                        <a
                                            href="#"
                                            data-ca-store-locator-location-element="city"
                                            data-ca-store-locator-location-city="{$city}"
                                            data-ca-store-locator-location-state="{$state_id}"
                                            data-ca-store-locator-location-state-name="{$state.title}"
                                            data-ca-store-locator-location-country="{$country_id}"
                                            data-ca-store-locator-location-country-name="{$country.title}"
                                            class="cm-dialog-closer"
                                        >{$city}</a>
                                    </li>
                                {/foreach}
                            </ul>
                        </li>
                    {/foreach}
                </ul>
            </li>
        {/foreach}
    </ul>
{/if}
