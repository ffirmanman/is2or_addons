{capture name="mainbox"}
    {capture name="sidebar"}
        {include file="addons/sg_quotation/views/sg_quotation/components/search_form.tpl" dispatch="sg_quotation.manage_quotation"}
    {/capture}
    {include file="common/pagination.tpl"}
    <form action="{""|fn_url}" method="post" name="quotation_requests_form">
        <table class="table table-middle table--relative table-responsive sortable">
            <thead>
                <tr>
                    <th>#</th>
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
                    {* <th>{__("product")}</th> *}
                    <th data-th="{__("customer")}">{__("customer")}</th>
                    <th data-th="{__("email")}">{__("email")}</th>
                    {* <th>{__("quantity")}</th> *}
                    <th data-th="{__("price")}">{__("price")}</th>
                    <th data-th="{__("date")}">{__("date")}</th>
                    <th data-th="{__("order_id")}">{__("order_id")}</th>
                    {* <th>{__("note")}</th> *}
                    <th></th>
                    <th data-th="{__("sg_status.status")}">{__("sg_status.status")|ucwords}</th>
                </tr>
            </thead>
            <tbody>
            {foreach from=$quotationList item="quote" key="key"}
                <tr>
                    <td data-th="#">{$quote.quotation_id}</td>
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
                    <td data-th="{__("customer")}"><a href="{'profiles.update&user_id='|fn_url}{$quote.user_id}">{$quote.user_id|fn_get_user_name}</a></td>
                    <td data-th="{__("email")}"><a href="{'profiles.update&user_id='|fn_url}{$quote.user_id}">{$quote.customer_email}</a></td>
                    <td data-th="{__("price")}">{include file="common/price.tpl" value=$quote.total|fn_format_price}</td>
                    <td data-th="{__("date")}">{$quote.created_at|date_format:$settings.Appearance.date_format}</td>
                    <td data-th="{__("order_id")}">{if $quote.order_id}<a href="{'orders.details&order_id='|fn_url}{$quote.order_id}">{$quote.order_id}</a>{else}--{/if}</td>
                    <td><div class="hidden-tools">
                        {if !$hide_delete_button && !$view_only}
                            {capture name="tools_list"}
                                <li>
                                    {include file="common/popupbox.tpl"
                                        id="quotation_detail_`$quote.quotation_id`"
                                        act="edit"
                                        link_class="cm-dialog-destroy-on-close"
                                        link_text=__("view_detail")
                                        text=__("view_detail")
                                        href="sg_quotation.quotation_detail?id={$quote.quotation_id}"
                                    }
                                </li>
                                {if $quote.status != "C"}
                                <li>
                                    {include file="common/popupbox.tpl"
                                        id="quotation_discount_`$quote.quotation_id`"
                                        act="edit"
                                        link_class="cm-dialog-destroy-on-close"
                                        link_text=__("apply_discount")
                                        text=__("apply_discount")
                                        href="sg_quotation.quotation_discount_popup?id={$quote.quotation_id}"
                                    }
                                </li>
                                {/if}
                                <li>{btn type="list" class="cm-post cm-confirm" text=__("delete") href="sg_quotation.delete?quotation_id=`$quote.quotation_id`"}</li>
                            {/capture}
                            {dropdown content=$smarty.capture.tools_list}
                        {/if}
                        </div>
                    </td>
                    <td data-th="{__("sg_status.status")}">
                        {if $quote.status == "D"}
                            <span class="cm-status-d ">
                                <span class="label label-important">{__("not_approved")}</span>
                                {btn type="text"
                                    id="quotation_disapprove"
                                    title=__("approve")
                                    icon="icon-thumbs-up"
                                    icon_first=true
                                    class="btn post__btn-status-switch cm-status-switch"
                                    data=[
                                        "data-ca-status"=>"A",
                                        "data-ca-post-id"=>$quote.quotation_id
                                    ]
                                }
                            </span>
                        {elseif $quote.status == "C"}
                            <span class="cm-status-c ">
                                <span class="label label-complete">{__("completed")}</span>
                            </span>
                        {elseif $quote.status == "A"}
                            <span class="cm-status-a">
                                <span class="label label-success">{__("approved")}</span>
                                {include file="common/popupbox.tpl"
                                    id="quotation_disaprove_`$quote.quotation_id`"
                                    act="edit"
                                    icon="icon-thumbs-down"
                                    icon_first=true
                                    link_class="cm-dialog-destroy-on-close quotationdisapprovelink"
                                    link_text=__("disapprove")
                                    text=__("disapprove")
                                    href="sg_quotation.disaprove?id={$quote.quotation_id}&status=D"
                                }
                            </span>
                        {elseif $quote.status == "O"}
                            <span class="cm-status-o ">
                                <span class="label label-important">{__("new")}</span>
                                {btn type="text"
                                    id="quotation_approve"
                                    title=__("approve")
                                    icon="icon-thumbs-up"
                                    icon_first=true
                                    class="btn post__btn-status-switch cm-status-switch"
                                    data=[
                                        "data-ca-status"=>"A",
                                        "data-ca-post-id"=>$quote.quotation_id
                                    ]
                                }
                                {include file="common/popupbox.tpl"
                                    id="quotation_disaprove_`$quote.quotation_id`"
                                    act="edit"
                                    icon="icon-thumbs-down"
                                    icon_first=true
                                    link_class="cm-dialog-destroy-on-close quotationdisapprovelink"
                                    link_text=__("disapprove")
                                    text=__("disapprove")
                                    href="sg_quotation.disaprove?id={$quote.quotation_id}&status=D"
                                }
                            </span>
                        {/if}
                    </td>
                </tr>
                <tr id="sg_quotation_products_{$key}"
                    class="row-more {if $hide_extra_button != "Y"}hidden{/if}">
                    <td colspan="9" class="row-more-body row-more-body--not-title top row-gray">
                        {if $quote.product_data}
                            <table class="ty-table ty-wallet-view__table" style="width: 100%;">
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
                                        <td data-th="{__("product_name")}">{$prod.product_name}</td>
                                        <td data-th="{__("required_qty")}">{$prod.required_qty}</td>
                                        <td data-th="{__("price")}">{include file="common/price.tpl" value=$prod.price|fn_format_price}</td>
                                    </tr>
                                {/foreach}
                                <tr class="">
                                    <td data-th="{__("total")}">{__('total')}</td>
                                    <td data-th="{__("required_qty")}">{$quote.totalQuantity}</td>
                                    <td data-th="{__("price")}">{include file="common/price.tpl" value=$quote.total|fn_format_price}</td>
                                </tr>
                                {if $quote.discount}
                                    <tr class="hidden-phone">
                                        <td>{__('discount')}</td>
                                        <td></td>
                                        <td>{include file="common/price.tpl" value=$quote.discounted_price|fn_format_price}</td>
                                    </tr>
                                    <tr class="hidden-phone">
                                        <td>{__('quotation_total')}</td>
                                        <td></td>
                                        <td>{include file="common/price.tpl" value=($quote.total-$quote.discounted_price)|fn_format_price}</td>
                                    </tr>

                                    <tr class="hidden-tablet hidden-desktop">
                                        <td data-th="{__("discount")}">{include file="common/price.tpl" value=$quote.discounted_price|fn_format_price}</td>
                                    </tr>
                                    <tr class="hidden-tablet hidden-desktop">
                                        <td data-th="{__("quotation_total")}">{include file="common/price.tpl" value=($quote.total-$quote.discounted_price)|fn_format_price}</td>
                                    </tr>
                                {/if}
                                </tbody>
                            </table>
                        {/if}
                    </td>
                </tr>
            {foreachelse}
                <tr>
                    <td colspan="9">
                        <p class="no-items">{__("no_data")}</p>
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </form>
    {include file="common/pagination.tpl"}

{/capture}

{capture name="adv_buttons"}
    {include file="common/tools.tpl" tool_href="sg_quotation.create_quotation" prefix="bottom" hide_tools="true" title=__("create_quotation") icon="icon-plus"}
{/capture}

{include file="common/mainbox.tpl"
    title=__("quotation_requests")
    content=$smarty.capture.mainbox
    buttons=$smarty.capture.buttons
    sidebar=$smarty.capture.sidebar
    content_id="quotation_requests_form"
    adv_buttons=$smarty.capture.adv_buttons
}
{* {$auth.backoffice_color_scheme|fn_print_r} *}
<style>
    a.quotationdisapprovelink {
        color: rgb(45, 45, 45);
        font-size: 0 !important; /* hides text without breaking layout */
    }

    a.quotationdisapprovelink svg {
    font-size: 20px !important; /* restore size for the icon */
    }

    .label-complete{
        background-color: #178351;
    }

    @media (min-width: 600px) {
        .hidden-tablet, .hidden-desktop{
            display: none;
        }
    }
</style>

{if $auth.backoffice_color_scheme == 'dark_mode'}
    <style>
        a.quotationdisapprovelink {
            color: rgb(227, 227, 227);
        }
    </style>
{/if}
<script>
    (function (_, $) {
        (function ($) {
            function _check_status_switch(data, params) {
            if (params.obj) {
                var $ = Tygh.$;
                var status = data.return_status ? data.return_status : params.status;
                var s_elm = $(params.obj).parents('.cm-statuses:first');
                s_elm.children('[class^="cm-status-"]').hide();
                s_elm.children('.cm-status-' + status.toLowerCase()).show();
            }
            }

            var methods = {
            status_switch: function status_switch(elm) {
                var jelm = $(elm);
                var data = {
                method: 'post',
                obj: jelm,
                status: jelm.data('caStatus'),
                callback: _check_status_switch
                };
                var href = fn_url('sg_quotation.update_status?table=sg_quotations&id_name=quotation_id&id=' + jelm.data('caPostId') + '&status=' + jelm.data('caStatus'));
                $.ceAjax('request', href, data);
                window.location.reload();
            }
            };
            $.extend({
            ceDiscussion: function ceDiscussion(method) {
                if (methods[method]) {
                return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
                } else {
                $.error('ty.discussion: method ' + method + ' does not exist');
                }
            }
            });
        })($);

        $(document).ready(function () {
            $(_.doc).on('click', '.cm-status-switch', function (e) {
                console.log('status_switch', e.currentTarget)
                $.ceDiscussion('status_switch', e.currentTarget);
            });
        });
    })(Tygh, Tygh.$);
    $(document).on("keypress", "#discount", function(e) {
        // Prevent minus sign
        console.log('keypress');
        if (e.key === "-" || e.keyCode === 45) {
            e.preventDefault();
        }
    });

    $(document).on("input", "#discount", function () {
        console.log('input');
        let v = parseFloat($(this).val());
        if (v < 0 || isNaN(v)) {
            $(this).val(0);
        }
    });
    
</script>