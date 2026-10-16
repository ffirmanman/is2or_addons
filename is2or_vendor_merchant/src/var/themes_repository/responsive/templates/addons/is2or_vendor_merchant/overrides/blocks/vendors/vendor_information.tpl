{** block-description:block_vendor_information **}

{$obj_id=$vendor_info.company_id}
{$obj_id_prefix="`$obj_prefix``$obj_id`"}
{include file="common/company_data.tpl" company=$vendor_info show_name=true show_descr=true show_rating=true show_logo=true show_links=true show_address=true show_location_full=true}
<div class="ty-company-detail clearfix">

    <div id="block_company_{$vendor_info.company_id}" class="clearfix">
        <h1 class="ty-mainbox-title">{$vendor_info.company}</h1>
        
        <div class="ty-company-detail__info">
            <div class="ty-company-detail__logo">
                {$capture_name="logo_`$obj_id`"}
                {$smarty.capture.$capture_name nofilter}
            </div>

            {if $addons.vendor_rating.status === 'A'}
                <div class="ty-company-detail__info-list ty-company-detail_info-first">
                    <h5 class="ty-company-detail__info-title">{__("rating")}</h5>
                    <div class="ty-company-detail__control-group">
                        <span>Orders success rate: <strong>{$vendor_info.company_id|fn_is2or_vendor_merchant_get_company_success_order_rate}%</strong></span>
                    </div>
                    <div class="ty-company-detail__control-group">
                        <span>Total orders: <strong>{$vendor_info.company_id|fn_is2or_vendor_merchant_get_company_total_orders}</strong></span>
                    </div>
                    <div class="ty-company-detail__control-group">
                        <span>Total products sold: <strong>{$vendor_info.company_id|fn_is2or_vendor_merchant_get_company_products_sold:true}</strong></span>
                    </div>
                    <div class="ty-company-detail__control-group">
                        <span>Average products rating: <strong>{$vendor_info.company_id|fn_is2or_vendor_merchant_get_company_average_products_rating}</strong> of 5</span>
                    </div>
                </div>
            {/if}
            {capture name = "profile_fields_{$obj_id}"}
                {foreach $profile_fields["ProfileFieldSections::CONTACT_INFORMATION"|enum] as $field_id => $field_data}
                    {if $field_data.field_type !== "ProfileFieldTypes::EMAIL"|enum && ($field_data.field_type !== "ProfileFieldTypes::PHONE"|enum || $field_data.autocomplete_type !== "phone-full")}
                        {$data_source = $vendor_info.fields}
                        {if $field_data.is_default === "YesNo::YES"|enum}
                            {$field_id = $field_data.field_name}
                            {$data_source = $vendor_info}
                        {/if}
                        {if !$data_source[$field_id]}
                            {continue}
                        {/if}
                        {$field_value = $data_source[$field_id]}
                        <div class="ty-company-detail__control-group">
                            {hook name="companies:profile_field_value"}
                                <label class="ty-company-detail__control-label">{$field_data.description}:</label>
                                {if $field_data.field_type === "ProfileFieldTypes::EMAIL"|enum}
                                    <span><a href="mailto:{$field_value}">{$field_value}</a></span>
                                {elseif $field_data.field_type === "ProfileFieldTypes::CHECKBOX"|enum}
                                    <span>{if $field_value === "YesNo::YES"|enum}{__("yes")}{else}{__("no")}{/if}</span>
                                {elseif $field_data.field_type === "ProfileFieldTypes::DATE"|enum}
                                    <span>{$field_value|date_format:"`$settings.Appearance.date_format`"}</span>
                                {elseif $field_data.field_type === "ProfileFieldTypes::RADIO"|enum
                                    || $field_data.field_type === "ProfileFieldTypes::SELECT_BOX"|enum
                                }
                                    <span>{$field_data.values.$field_value}</span>
                                {elseif $field_data.field_type === "ProfileFieldTypes::FILE"|enum && $field_value.file_name}
                                    <span><a href="{$field_value.link|default:""}">{$field_value.file_name}</a></span>
                                {elseif $field_id === "url"} {* FIXME: URL display is hardcoded *}
                                    <span><a href="{$field_value|normalize_url}">{$field_value}</a></span>
                                {elseif $field_data.field_type === "ProfileFieldTypes::PHONE"|enum || ($field_data.autocomplete_type === "phone-full")}
                                    <span><bdi>{$field_value}</bdi></span>
                                {elseif $field_data.field_type === "ProfileFieldTypes::COUNTRY"|enum}
                                    <span><bdi>{$field_value|fn_get_country_name}</bdi></span>
                                {elseif $field_data.field_type === "ProfileFieldTypes::STATE"|enum}
                                    <span><bdi>{$field_value|fn_get_state_name:$vendor_info.country}</bdi></span>
                                {else}
                                    <span>{$field_value}</span>
                                {/if}
                            {/hook}
                        </div>
                    {/if}
                {/foreach}
            {/capture}

            {if $smarty.capture["profile_fields_{$obj_id}"]|trim}
                <div class="ty-company-detail__info-list">
                    <h5 class="ty-company-detail__info-title">{__("contact_information")}</h5>
                    {$smarty.capture["profile_fields_{$obj_id}"] nofilter}
                </div>
            {/if}

            {$address="address_`$obj_id`"}
            {$location_full="location_full_`$obj_id`"}
            {if
                $smarty.capture.$address|trim
                || $smarty.capture.$location_full|trim
                || $vendor_info.country
            }
                <div class="ty-company-detail__info-list ty-company-detail_info-first">
                    <h5 class="ty-company-detail__info-title">{__("shipping_address")}</h5>

                    {if $smarty.capture.$address|trim}
                        <div class="ty-company-detail__control-group">
                            <span>{$smarty.capture.$address nofilter}</span>
                        </div>
                    {/if}

                    {if $smarty.capture.$location_full|trim}
                        <div class="ty-company-detail__control-group">
                            <span>{$smarty.capture.$location_full nofilter}</span>
                        </div>
                    {/if}

                    <div class="ty-company-detail__control-group">
                        <span>{$vendor_info.country|fn_get_country_name}</span>
                    </div>
                </div>
            {/if}
        </div>
    </div>
</div>
