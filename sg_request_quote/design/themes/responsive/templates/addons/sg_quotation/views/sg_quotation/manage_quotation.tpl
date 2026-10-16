{capture name="tabsbox"}
    
    <div class="ty-request_quotaion form-wrap"  id="content_general">
        {* <div class="ty-margint">
            <a style="margin-left:5px;" href="{"wk_request_a_quotation.addquote"|fn_url}" class="ty-btn ty-btn__secondary" rel="nofollow">{__("add_quotation")}</a>
            <a style="margin-right: 5px;float: right;" href='{"wk_request_a_quotation.priceCatalog"|fn_url}' class="ty-btn ty-btn__primary" rel="nofollow">{__("price_catalog")}</a>
        </div>   *}
        {if $quotationList}
            <form action="{""|fn_url}" method="post" target="_self" name="request_quotaions_form">
                
                {assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
                {assign var="c_dummy" value="<i class=\"exicon-dummy\"></i>"}
                {if $search.sort_order_rev eq 'asc'}
                {assign var="c_icon" value="<i class=\"ty-icon-up-dir\"></i>"}
                {else}
                {assign var="c_icon" value="<i class=\"ty-icon-down-dir\"></i>"}
                {/if}
                {assign var="rev" value=$smarty.request.content_id|default:"pagination_contents"}
                {include file="common/pagination.tpl"}
                <table class="ty-table ty-wallet-view__table">
                    <thead>           
                        <tr>
                            <th>{__('id')}</th>
                            <th width="5%">
                                <div class="btn-expand-wrapper">
                                    <span id="on_st"
                                        alt="{__("expand_collapse_list")}"
                                        title="{__("expand_collapse_list")}"
                                        class=" hand cm-combinations-visitors btn-expand btn-expand--header">
                                        <span class="icon-caret-right cs-dark-theme-invert"></span>
                                    </span>
                                    <span id="off_st"
                                        alt="{__("expand_collapse_list")}"
                                        title="{__("expand_collapse_list")}"
                                        class="hand hidden cm-combinations-visitors btn-expand btn-expand--header">
                                        <span class="icon-caret-down cs-dark-theme-invert"></span>
                                    </span>
                                </div>
                            </th>
                            <th>{__("customer")}</th>
                            <th>{__("email")}</th>
                            {* <th>{__("quantity")}</th> *}
                            <th>{__("price")}</th>
                            <th>{__("date")}</th>
                            <th>{__("sg_status.status")}</th>
                            <th>{__('proceed')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$quotationList item="quote" key="key"}
                            <tr>
                                <td>{$quote.quotation_id}</td>
                                <td class="left status-{$sg_data.status|lower}">
                                    <button type="button"
                                            name="plus_minus"
                                            id="on_sg_quotation_products_{$key}"
                                            alt="{__("expand_collapse_list")}"
                                            title="{__("expand_collapse_list")}"
                                            class="hand cm-combination-visitors btn-expand">
                                        <span class="icon-caret-right cs-dark-theme-invert"></span>
                                    </button>
                                    <button type="button"
                                            name="minus_plus"
                                            id="off_sg_quotation_products_{$key}"
                                            alt="{__("expand_collapse_list")}"
                                            title="{__("expand_collapse_list")}"
                                            class="hand hidden cm-combination-visitors btn-expand">
                                        <span class="icon-caret-down cs-dark-theme-invert"></span>
                                    </button>
                                </td>
                                {* <td><a href="{'products.view&product_id='|fn_url}{$quote.product_id}">{$quote.product_id|fn_get_product_name}</a></td> *}
                                <td><a href="{'profiles.update&user_id='|fn_url}{$quote.user_id}">{$quote.user_id|fn_get_user_name}</a></td>
                                <td><a href="{'profiles.update&user_id='|fn_url}{$quote.user_id}">{$quote.customer_email}</a></td>
                                {* <td>{$quote.required_qty}</td> *}
                                <td>{include file="common/price.tpl" value=$quote.total|fn_format_price}</td>
                                <td>{$quote.created_at|date_format:$settings.Appearance.date_format}</td>
                                {* <td>{$quote.note}</td> *}
                                <td>
                                    {if $quote.status == "D"}
                                        <span class="cm-status-d ">
                                            <span class="label label-important">{__("not_approved")}</span>
                                        </span>
                                    {elseif $quote.status == "C"}
                                        <span class="cm-status-c ">
                                            <span class="label label-important">{__("completed")}</span>
                                        </span>
                                    {elseif $quote.status == "A"}
                                        <span class="cm-status-a">
                                            <span class="label label-success">{__("approved")}</span>
                                        </span>
                                    {elseif $quote.status == "O"}
                                        <span class="cm-status-o ">
                                            <span class="label label-important">{__("new")}</span>
                                        </span>
                                    {/if}
                                </td>
                                <td>
                                    {if $quote.status == "A" && $quote.placeorderhref}
                                        <span class="cm-status-a">
                                            <span><a class="ty-btn ty-btn__primary" href="{$quote.placeorderhref}" target="_blank" >{__('pay_now')}</a></span>
                                        </span>
                                    {/if}
                                </td>
                            </tr>
                            <tr id="sg_quotation_products_{$key}"
                                class="row-more {if $hide_extra_button != "Y"}hidden{/if}">
                                <td colspan="9" class="row-more-body row-more-body--not-title top row-gray">
                                    {if $quote.product_data}
                                        <table class="ty-table ty-wallet-view__table">
                                            <thead>           
                                                <tr>
                                                    <th>{__("product_name")}</th>
                                                    <th>{__("required_qty")}</th>
                                                    <th>{__("price")}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            {foreach from=$quote.product_data item="prod"}
                                                <tr>
                                                    <td>{$prod.product_name}</td>
                                                    <td>{$prod.required_qty}</td>
                                                    <td>{include file="common/price.tpl" value=$prod.price|fn_format_price}</td>
                                                </tr>
                                            {/foreach}
                                            <tr>
                                                <td>{__('total')}</td>
                                                <td>{$quote.totalQuantity}</td>
                                                <td>{include file="common/price.tpl" value=$quote.total|fn_format_price}</td>
                                            </tr>
                                            {if $quote.discount}
                                                <tr>
                                                    <td>{__('discount')}</td>
                                                    <td></td>
                                                    <td>{include file="common/price.tpl" value=$quote.discounted_price|fn_format_price}</td>
                                                </tr>
                                                <tr>
                                                    <td>{__('quotation_total')}</td>
                                                    <td></td>
                                                    <td>{include file="common/price.tpl" value=($quote.total-$quote.discounted_price)|fn_format_price}</td>
                                                </tr>
                                            {/if}
                                            </tbody>
                                        </table>
                                    {/if}
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
                {include file="common/pagination.tpl"}
            </form>
        {else}
            <div id="no_request_quotaion ">
                <p class="ty-no-items">{__("no_quotation_available")}</p>
            </div>
        {/if}    
    </div>
    {capture name="additional_tabs"}
    {if $quotation_history}
         
    {/if}
    {/capture}
    {$smarty.capture.additional_tabs nofilter}
{/capture}
{if $smarty.capture.additional_tabs|trim != ""}
    {include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$smarty.request.selected_section track=true}
{else}
    {$smarty.capture.tabsbox nofilter}
{/if}
{capture name="mainbox_title"}{__("quotation_requested")}{/capture}

<style>
    .ty-wallet-view__table{
        .hand.cm-combination-visitors.btn-expand{
            background: none;
            border: 0;
        }
        .icon-caret-down{
            display: block;
            width: 20px;
        }
        .icon-caret-down::after {
            content: url('../../../../../../../design/backend/media/../templates/icons/caret_down.svg?1762331699');
        }
        .icon-caret-right{
            display: block;
            width: 20px;
        }
        .icon-caret-right::after {
            content: url('../../../../../../../design/backend/media/../templates/icons/caret_right.svg?1762331699');
        }

    }
</style>