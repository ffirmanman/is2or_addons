{if fn_check_view_permissions('banners.manage', 'GET') && fn_abt__ut2_mv_is_banners_for_product_available()}
    <div class="hidden" id="content_abt__ut2_mv_banners_for_product">
        {foreach ["after_breadcrumbs", "after_general"] as $position}
            <div class="control-group">
                <label class="control-label" for="banner_{$position}">
                    {__("abt__ut2_mv.banner_position.{$position}")}
                </label>
                <div class="controls">
                    <select name="abt__ut2_mv_banners[{$position}]" id="banner_{$position}">
                        <option value="">--</option>
                        {foreach $banners as $banner}
                            <option value="{$banner.banner_id}"{if $vendor_banners[$position]["banner_id"] == $banner.banner_id} selected{/if}>
                                {$banner.banner}
                            </option>
                        {/foreach}
                    </select>
                </div>
            </div>
        {/foreach}
    <!--content_abt__ut2_mv_banners_for_product--></div>
{/if}

{if fn_abt__ut2_mv_is_personal_design_available()}
    <div class="hidden" id="content_abt__ut2_mv_personal_design">
        {if fn_check_view_permissions('banners.manage', 'GET')}
            <div class="control-group">
                <label class="control-label" for="banner_main_page">
                    {__("abt__ut2_mv.banner_main_page")}
                </label>
                <div class="controls">
                    <select name="company_data[personal_design][banner_main_page]" id="banner_main_page">
                        <option value="">--</option>
                        {foreach $banners as $banner}
                            <option value="{$banner.banner_id}"{if $company_data.personal_design.banner_main_page == $banner.banner_id} selected{/if}>
                                {$banner.banner}
                            </option>
                        {/foreach}
                    </select>
                </div>
            </div>
        {/if}

        {* Subheader & Section: Colors - Vendor Info *}
        {include file="common/subheader.tpl" title=__("abt__ut2.less_settings.vendor.vendor_info_group") target="#vendor_info_colors"}
        <div id="vendor_info_colors" class="in collapse">
            <p>{__("abt__ut2.less_settings.vendor.vendor_info_group_description")}</p>
            
            <div class="control-group">
                <label class="control-label">{__("abt__ut2_mv.info_block_background")}:</label>
                <div class="controls">
                    {include file="common/attach_images.tpl" 
                        image_name="info_block_background" 
                        image_object_type="abt__v_info_block_bg" 
                        image_pair=$company_data.personal_design.abt__v_info_block_bg 
                        image_object_id=$company_data.company_id 
                        no_thumbnail=true
                    }
                </div>
            </div>

            {foreach ["background", "font_main", "font_additional", "links"] as $c_type}
                {$val = $company_data.personal_design["vendor_info_{$c_type}"]}
                <div class="control-group">
                    <label class="control-label cm-color" for="abt__ut2_mv.vendor_info_{$c_type}">
                        {__("abt__ut2.less_settings.vendor.vendor_info.{$c_type}")}:
                    </label>
                    <div class="controls">
                        {include file="views/theme_editor/components/colorpicker.tpl" cp_name="company_data[personal_design][vendor_info_{$c_type}]" cp_id="abt__ut2_mv.vendor_info_{$c_type}" cp_value=$val|replace:"transparent":""}
                    </div>
                </div>
            {/foreach}
        </div>

        {* Subheader & Section: Colors - Vendor Menu *}
        {include file="common/subheader.tpl" title=__("abt__ut2.less_settings.vendor.vendor_menu_group") target="#vendor_menu_colors"}
        <div id="vendor_menu_colors" class="in collapse">
            <p>{__("abt__ut2.less_settings.vendor.vendor_menu_group_description")}</p>
            
            {foreach ["background", "active_background", "item", "item_active"] as $c_type}
                {$val = $company_data.personal_design["vendor_menu_{$c_type}"]}
                <div class="control-group">
                    <label class="control-label cm-color" for="abt__ut2_mv.vendor_info_{$c_type}">
                        {__("abt__ut2.less_settings.vendor.vendor_menu.{$c_type}")}:
                    </label>
                    <div class="controls">
                        {include file="views/theme_editor/components/colorpicker.tpl" cp_name="company_data[personal_design][vendor_menu_{$c_type}]" cp_id="abt__ut2_mv.vendor_info_{$c_type}" cp_value=$val|replace:"transparent":""}
                    </div>
                </div>
            {/foreach}
        </div>

        {* Subheader & Section: Colors - Vendor Buttons *}
        {include file="common/subheader.tpl" title=__("abt__ut2.less_settings.vendor.vendor_buttons_group") target="#vendor_buttons_colors"}
        <div id="vendor_buttons_colors" class="in collapse">
            <p>{__("abt__ut2.less_settings.vendor.vendor_buttons_group_description")}</p>
            
            {foreach ["background", "active_background", "color", "color_active"] as $c_type}
                {$val = $company_data.personal_design["vendor_buttons_{$c_type}"]}
                <div class="control-group">
                    <label class="control-label cm-color" for="abt__ut2_mv.vendor_info_{$c_type}">
                        {__("abt__ut2.less_settings.vendor.vendor_buttons.{$c_type}")}:
                    </label>
                    <div class="controls">
                        {include file="views/theme_editor/components/colorpicker.tpl" cp_name="company_data[personal_design][vendor_buttons_{$c_type}]" cp_id="abt__ut2_mv.vendor_info_{$c_type}" cp_value=$val|replace:"transparent":""}
                    </div>
                </div>
            {/foreach}
        </div>
    <!--content_abt__ut2_mv_personal_design--></div>
{/if}

{* Section: Social Links *}
<div class="hidden" id="content_abt__ut2_mv_social_links">
    <!-- this list are created by cscart core code new version -->
    {foreach ["facebook", "youtube", "telegram", "instagram", "tiktok", "twitter", "pinterest", "linkedin", "vk", "snapchat"] as $soc_media}
        <div class="control-group">
            <label class="control-label" for="social_links_{$soc_media}">
                {__("abt__ut2_mv.social_links.{$soc_media}")}
            </label>
            <div class="controls">
                <input type="text" name="company_data[social_links][{$soc_media}]" id="social_links_{$soc_media}" value="{$company_data.social_links.$soc_media}">
            </div>
        </div>
    {/foreach}
<!--content_abt__ut2_mv_social_links-->
</div>