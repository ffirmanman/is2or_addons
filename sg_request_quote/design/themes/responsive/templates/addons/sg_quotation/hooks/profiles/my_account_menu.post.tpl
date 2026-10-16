{if $smarty.session.auth.user_id}
    <li class="ty-account-info__item ty-dropdown-box__item">
        <a href="{"sg_quotation.add_quotation"|fn_url}" rel="nofollow" class="ty-account-info__a">{__("sg_add_quotation")}</a>
    </li>
    <li class="ty-account-info__item ty-dropdown-box__item">
        <a href="{"sg_quotation.manage_quotation"|fn_url}" rel="nofollow" class="ty-account-info__a">{__("sg_quotations")}</a>
    </li>
{/if}