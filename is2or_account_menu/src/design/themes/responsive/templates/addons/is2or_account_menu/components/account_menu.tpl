{assign var="return_current_url" value=$config.current_url|escape:url}
<div class="ty-vc-account-menu">
    <div class="ty-vc-account-menu__user">
        {include_ext file="common/icon.tpl" class="ty-icon-user ty-vc-account-menu__user-icon"}
        <div class="ty-vc-account-menu__user-info">
            {if $auth.user_id}
                {if $user_info.firstname || $user_info.lastname}
                    <div class="ty-vc-account-menu__user-name">{$user_info.firstname} {$user_info.lastname}</div>
                {/if}
                <div class="ty-vc-account-menu__user-email">{$user_info.email}</div>
            {else}
                <div class="ty-vc-account-menu__user-name">{$user_data.firstname} {$user_data.lastname}</div>
                <div class="ty-vc-account-menu__user-email">{$user_data.email}</div>
            {/if}
        </div>
    </div>

    <ul>
        <li>
            <a href="{"orders.search"|fn_url}" class="{if $runtime.controller == "orders"}active{/if}">
                {include_ext file="common/icon.tpl" class="ty-icon-doc-text"}
                <span>{__("orders")}</span>
            </a>
        </li>
        <li>
            <a href="{"vendor_communication.threads"|fn_url}" class="{if $runtime.controller == "vendor_communication"}active{/if}">
                {include_ext file="common/icon.tpl" class="ty-icon-chat"}
                <span>{__("vendor_communication.messages")}</span>
            </a>
        </li>
        <li>
            <a href="{"wishlist.view"|fn_url}" class="{if $runtime.controller == "wishlist"}active{/if}">
                {include_ext file="common/icon.tpl" class="ty-icon-heart"}
                <span>{__("wishlist")}</span>
            </a>
        </li>
        {if $settings.General.enable_compare_products == 'Y'}
            <li>
                <a href="{"product_features.compare"|fn_url}" class="{if $runtime.controller == "product_features"}active{/if}">
                    {include_ext file="common/icon.tpl" class="ty-icon-chart-bar"}
                    <span>{__("view_comparison_list")}</span>
                </a>
            </li>
        {/if}
        <li>
            <a href="{"reward_points.userlog"|fn_url}" class="{if $runtime.controller == "reward_points"}active{/if}">
                {include_ext file="common/icon.tpl" class="ty-icon-star"}
                <span>{__("my_points")}</span>
            </a>
        </li>
        <li>
            <a href="{"profiles.update"|fn_url}" class="{if $runtime.controller == "profiles"}active{/if}">
                {include_ext file="common/icon.tpl" class="ty-icon-user"}
                <span>{__("profile_details")}</span>
            </a>
        </li>
        <li>
            <a href="{"address_selector.manage"|fn_url}" class="{if $runtime.controller == "address_selector"}active{/if}">
                {include_ext file="common/icon.tpl" class="ty-icon-location-arrow"}
                <span>{__("is2or_address_selector.manage_addresses")}</span>
            </a>
        </li>
        <li>
            <a href="{"is2or_notification_user.index"|fn_url}" class="{if $runtime.controller == "is2or_notification_user"}active{/if}">
                {include_ext file="common/icon.tpl" class="ty-icon-bubble"}
                <span>{__("is2or_notification_user.title")}</span>
            </a>
        </li>
        <li>
            <a href="{"is2or_login_activity.index"|fn_url}" class="{if $runtime.controller == "is2or_login_activity"}active{/if}">
                {include_ext file="common/icon.tpl" class="ty-icon-lock"}
                <span>{__("security")}</span>
            </a>
        </li>
       <li>
            <a href="{"auth.logout?redirect_url="|fn_url}" rel="nofollow">
                {include_ext file="common/icon.tpl" class="ty-icon-back"}
                <span>{__("sign_out")}</span>
            </a>
        </li>
    </ul>
</div>
