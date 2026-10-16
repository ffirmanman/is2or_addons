{if $auth.user_id}
    <li class="ty-account-info__item ty-dropdown-box__item">
        <a class="ty-account-info__a" href="{"address_selector.manage"|fn_url}" rel="nofollow">{__("is2or_address_selector.manage_addresses")}</a>
    </li>
{/if}
