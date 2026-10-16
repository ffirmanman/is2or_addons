{$selection_mode = $selection_mode|default:"link"}
{$return_url = $return_url|default:$config.current_url}

{if $profiles}
    <div class="is2or-address-selector__grid" data-ca-address-selector-active-label="{__("is2or_address_selector.selected_address")}">
        {foreach $profiles as $profile}
            {$is_active = $profile.profile_id == $active_profile_id}
            <div class="is2or-address-selector__card {if $is_active}is-active{/if} {if !$profile.is_selectable}is-incomplete{/if}"
                data-ca-address-selector-card-profile-id="{$profile.profile_id}">
                <div class="is2or-address-selector__head">
                    <h5 class="is2or-address-selector__title">
                        {$profile.profile_name|default:__("is2or_address_selector.addresses")}
                    </h5>

                    <div class="is2or-address-selector__badges">
                        {if $profile.is_primary}
                            <span class="is2or-address-selector__badge">{__("is2or_address_selector.primary_address")}</span>
                        {/if}
                        {if $is_active}
                            <span class="is2or-address-selector__badge is2or-address-selector__badge--active">{__("is2or_address_selector.selected_address")}</span>
                        {/if}
                        {if !$profile.is_selectable}
                            <span class="is2or-address-selector__badge is2or-address-selector__badge--warning">{__("is2or_address_selector.incomplete_address")}</span>
                        {/if}
                    </div>
                </div>

                <div class="is2or-address-selector__lines">
                    {if $profile.s_address || $profile.s_address_2 || $profile.display_city || $profile.display_country}
                        {if $profile.s_address}
                            <div>{$profile.s_address}</div>
                        {/if}
                        {if $profile.s_address_2}
                            <div>{$profile.s_address_2}</div>
                        {/if}
                        {if $profile.display_city || $profile.display_state || $profile.s_zipcode}
                            <div>
                                {if $profile.display_city}{$profile.display_city}{/if}
                                {if $profile.display_city && ($profile.display_state || $profile.s_zipcode)}, {/if}
                                {if $profile.display_state}{$profile.display_state}{/if}
                                {if $profile.display_state && $profile.s_zipcode}, {/if}
                                {if $profile.s_zipcode}{$profile.s_zipcode}{/if}
                            </div>
                        {/if}
                        {if $profile.display_country}
                            <div>{$profile.display_country}</div>
                        {/if}
                    {else}
                        <div class="is2or-address-selector__empty">{__("is2or_address_selector.no_address_details")}</div>
                    {/if}
                </div>

                <div class="is2or-address-selector__actions">
                    {if $show_select}
                        {if $selection_mode === "ajax"}
                            <button
                                type="button"
                                class="ty-btn ty-btn__primary cm-dialog-closer js-address-selector-select"
                                data-ca-address-selector-url="{"address_selector.select"|fn_url}"
                                data-ca-address-selector-profile-id="{$profile.profile_id}"
                                data-ca-address-selector-is-checkout-page="{if $runtime.controller === "checkout" && $runtime.mode === "checkout"}1{else}0{/if}"
                                {if $is_active || !$profile.is_selectable}disabled="disabled"{/if}
                            >{__("is2or_address_selector.use_this_address")}</button>
                        {elseif $profile.is_selectable && !$is_active}
                            <a
                                class="ty-btn ty-btn__primary"
                                href="{"address_selector.select?profile_id=`$profile.profile_id`&return_url=`$return_url|escape:url`"|fn_url}"
                            >{__("is2or_address_selector.use_this_address")}</a>
                        {/if}
                    {/if}

                    {if $show_edit}
                        <a class="ty-btn ty-btn__secondary" href="{"address_selector.update?profile_id=`$profile.profile_id`"|fn_url}">
                            {__("is2or_address_selector.edit_address")}
                        </a>
                    {/if}

                    {if $show_delete && !$profile.is_primary}
                        <a
                            class="ty-btn ty-btn__tertiary cm-confirm"
                            href="{"address_selector.delete?profile_id=`$profile.profile_id`&return_url=`$return_url|escape:url`"|fn_url}"
                        >{__("delete")}</a>
                    {/if}
                </div>

                {if !$profile.is_selectable}
                    <div class="is2or-address-selector__lead">{__("is2or_address_selector.complete_address_to_select")}</div>
                {/if}
            </div>
        {/foreach}
    </div>
{/if}
