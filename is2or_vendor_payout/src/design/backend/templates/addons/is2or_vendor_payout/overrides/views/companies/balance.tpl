{capture name="mainbox"}

    {if $runtime.company_id}
        {literal}
        <style>
        .balance-cards {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .balance-card {
            flex: 1;
            background: #fff;
            border: 1px solid #dce3e8;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            padding: 15px 20px;
            text-align: center;
            transition: 0.2s;
        }

        .balance-card:hover {
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        }

        .balance-card-header {
            font-size: 14px;
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .balance-card-value {
            font-size: 24px;
            font-weight: 700;
        }

        .is2or-payout-detail-wrap {
            display: flex !important;
            flex-direction: row !important;
            gap: 30px;
            flex-wrap: nowrap !important;
            align-items: flex-start;
            width: 100%;
        }

        .is2or-payout-detail-section {
            flex: 1 1 0 !important;
            min-width: 0 !important;
            width: 50%;
        }

        .is2or-payout-detail-section h6 {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #888;
            margin: 0 0 8px 0;
            padding-bottom: 4px;
            border-bottom: 1px solid #e0e0e0;
        }

        .is2or-payout-detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .is2or-payout-detail-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .is2or-payout-detail-table td:first-child {
            color: #888;
            width: 140px;
            padding-right: 10px;
            white-space: nowrap;
        }

        .is2or-payout-detail-table td:last-child {
            color: #333;
            font-weight: 500;
        }

        .is2or-receipt-image-thumb {
            max-width: 120px;
            max-height: 80px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .is2or-receipt-image-thumb:hover {
            opacity: 0.8;
        }

        .is2or-no-receipt {
            font-size: 12px;
            color: #aaa;
            font-style: italic;
        }
        </style>
        {/literal}
        
        <div class="balance-cards clearfix">
            <div class="balance-card">
                <div class="balance-card-header">{__('is2or_vendor_payout.available_balance')}</div>
                <div class="balance-card-value text-success">{include file="common/price.tpl" value=$current_balance}</div>
            </div>

            <div class="balance-card">
                <div class="balance-card-header">{__('is2or_vendor_payout.pending_balance')}</div>
                <div class="balance-card-value text-error">{include file="common/price.tpl" value=$pending_balance}</div>
            </div>

            <div class="balance-card">
                <div class="balance-card-header">{__('is2or_vendor_payout.total_balance')}</div>
                <div class="balance-card-value text-info">{include file="common/price.tpl" value=$total_balance}</div>
            </div>
        </div>
    {/if}

    {capture name="tabsbox"}
    {/capture}

    {include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$smarty.request.selected_section|default:"transactions" group_name="vendor_payouts"}

    {if $runtime.company_id}
        {assign var="hide_controls" value=true}
    {/if}

    {assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
    {include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
    {include_ext file="common/icon.tpl" class="icon-dummy" assign=c_dummy}
    <form action="{""|fn_url}" method="post" class="form-horizontal form-edit" name="manage_payouts_form" id="manage_payouts_form">

        {include file="common/pagination.tpl" save_current_page=true save_current_url=true}

        <input type="hidden" name="redirect_url" value="{$c_url}"/>
        {if $payouts}
            {capture name="payouts_table"}
                <div class="table-responsive-wrapper longtap-selection">
                    <table width="100%" class="table table-middle table--relative table-responsive" id="payouts_list">
                        <thead
                                data-ca-bulkedit-default-object="true"
                                data-ca-bulkedit-component="defaultObject"
                        >
                        <tr>
                            <th class="left">
                                {if !$hide_controls}
                                    {include file="common/check_items.tpl"}

                                    <input type="checkbox"
                                           class="bulkedit-toggler hide"
                                           data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                           data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                                    />
                                {/if}
                            </th>
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
                            <th width="5%">{__("status")}</th>
                            <th>
                                <a class="cm-ajax"
                                   href="{"`$c_url`&sort_by=sort_date&sort_order=`$search.sort_order_rev`"|fn_url}"
                                   data-ca-target-id="pagination_contents">
                                    {__("date")}{if $search.sort_by === "sort_date"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}
                                </a>
                            </th>
                            <th>{__("vendor_payouts.type")}</th>
                            {if !$hide_controls}
                                <th>{__("vendor")}</th>
                            {/if}
                            {hook name="companies:balance_list_th"}{/hook}
                            <th class="center" width="5%">&nbsp;</th>
                            <th width="15%" class="right">{__("vendor_payouts.transaction_value")}</th>
                        </tr>
                        </thead>
                        {foreach name="payouts" from=$payouts item=payout}
                            <tr class="payout payout-{$payout.payout_type|lower} cm-row-status-{$payout.approval_status|lower} cm-longtap-target"
                                data-ca-longtap-action="setCheckBox"
                                data-ca-longtap-target="input.cm-item"
                                data-ca-id="{$payout.payout_id}"
                            >
                                <td class="left mobile-hide">
                                    <input type="checkbox" name="payout_ids[]" value="{$payout.payout_id}" class="cm-item cm-item-status-{$payout.approval_status|lower} hide"/>
                                </td>
                                <td class="left approval-status-{$payout.approval_status|lower}">
                                    <button type="button"
                                          name="plus_minus"
                                          id="on_payout_note_{$smarty.foreach.payouts.iteration}"
                                          alt="{__("expand_collapse_list")}"
                                          title="{__("expand_collapse_list")}"
                                          class="hand cm-combination-visitors btn-expand">
                                        <span class="icon-caret-right cs-dark-theme-invert"></span>
                                    </button>
                                    <button type="button"
                                          name="minus_plus"
                                          id="off_payout_note_{$smarty.foreach.payouts.iteration}"
                                          alt="{__("expand_collapse_list")}"
                                          title="{__("expand_collapse_list")}"
                                          class="hand hidden cm-combination-visitors btn-expand">
                                        <span class="icon-caret-down cs-dark-theme-invert"></span>
                                    </button>
                                </td>
                                <td class="nowrap" data-th="{__("status")}">
                                    {if $payout.payout_type == "VendorPayoutTypes::PAYOUT"|enum
                                    || $payout.payout_type == "VendorPayoutTypes::WITHDRAWAL"|enum
                                    || $payout.payout_type == "VendorPayoutTypes::ORDER_PLACED"|enum
                                    }
                                        {include
                                            file="common/select_popup.tpl"
                                            type="companies_payouts"
                                            id=$payout.payout_id
                                            status=$payout.approval_status
                                            items_status=$approval_statuses
                                            notify_vendor=true
                                            update_controller="companies.payouts"
                                            st_return_url=$config.current_url
                                            st_result_ids="balance_total,payouts_list"
                                            hide_for_vendor=$hide_controls
                                        }
                                    {/if}
                                </td>
                                <td data-th="{__("date")}">
                                    {$payout.payout_date|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                                </td>
                                <td data-th="{__("vendor_payouts.type")}">
                                    {hook name="companies:payout_type_description"}
                                    {$payout.payout_type_description|sanitize_html nofilter}
                                    {/hook}
                                </td>
                                {if !$runtime.company_id}
                                    <td data-th="{__("vendor")}">
                                        {if $payout.company_id}
                                            {$payout.company|default:__("deleted")}
                                        {else}
                                            {$settings.Company.company_name}
                                        {/if}
                                    </td>
                                {/if}
                                {hook name="companies:balance_list_tr"}{/hook}
                                <td class="center nowrap" data-th="{__("tools")}">
                                    {if !$hide_controls}
                                        <div class="hidden-tools">
                                            {capture name="tools_list"}
                                                {if $payout.payout_type == "VendorPayoutTypes::WITHDRAWAL"|enum}
                                                    {* Has payment available *}
                                                    {if $payout_payment_available && $payout.is2or_payout_details && $payout.approval_status == "VendorPayoutApprovalStatuses::PENDING"|enum}
                                                        <li>
                                                            {btn type="list"
                                                                text=__("is2or_vendor_payout.pay_with_payment", [
                                                                    "[payment]" => $vendor_payout_payment.name
                                                                ])
                                                                href="is2or_vendor_payout.payout?payout_id=`$payout.payout_id`" class="cm-post cm-confirm"
                                                            }
                                                        </li>
                                                    {/if}

                                                    {* Has payment details *}
                                                    {if $payout.is2or_payout_result && $payout.approval_status == "VendorPayoutApprovalStatuses::COMPLETED"|enum}
                                                        <li>
                                                            {btn type="list"
                                                                text=__("is2or_vendor_payout.payout_details")
                                                                href="is2or_vendor_payout.detail?payout_id=`$payout.payout_id`" class="cm-dialog-opener cm-dialog-destroy-on-close"
                                                                data=[
                                                                    "data-ca-dialog-title" => __("is2or_vendor_payout.payout_details")
                                                                ]
                                                            }
                                                        </li>
                                                    {/if}

                                                    {* Pay with Bank Account *}
                                                    {if $payout.is2or_payout_details}
                                                        <li>
                                                            {btn type="list"
                                                                text=__("is2or_vendor_payout.pay_with_bank_account")
                                                                href="is2or_vendor_payout.bank_payment_info?payout_id=`$payout.payout_id`"
                                                                class="cm-dialog-opener cm-dialog-destroy-on-close"
                                                                data=[
                                                                    "data-ca-dialog-title" => __("is2or_vendor_payout.pay_with_bank_account")
                                                                ]
                                                            }
                                                        </li>
                                                    {/if}
                                                {/if}

                                                <li>{btn type="list" class="cm-confirm" text=__("delete") href="companies.payout_delete?payout_id=`$payout.payout_id`&redirect_url={$c_url|rawurlencode}" method="POST"}</li>
                                            {/capture}
                                            {dropdown content=$smarty.capture.tools_list}
                                        </div>
                                    {/if}
                                </td>
                                <td class="right" data-th="{__("vendor_payouts.transaction_value")}">
                                    {* total balance change *}
                                    {hook name="companies:payout_amount"}
                                        {if $payout.payout_type == "VendorPayoutTypes::PAYOUT"|enum && $payout.payout_amount < 0}
                                            <small class="muted">
                                                {include file="common/price.tpl" value=$payout.display_amount}
                                            </small>
                                        {else}
                                            {include file="common/price.tpl" value=$payout.display_amount}
                                        {/if}
                                    {/hook}
                                </td>
                            </tr>

                            {* ── Row expand: Request + Detail by Admin ── *}
                            <tr id="payout_note_{$smarty.foreach.payouts.iteration}"
                                class="row-more {if $hide_extra_button != "Y"}hidden{/if}">
                                <td colspan="8" class="row-more-body row-more-body--not-title top row-gray">
                                    <div class="is2or-payout-detail-wrap" style="display:flex; flex-direction:row; gap:30px; align-items:flex-start; width:100%;">

                                        {* ── Kolom kiri: Request (comment vendor) ── *}
                                        <div class="is2or-payout-detail-section" style="flex:1; min-width:0;">
                                            <h6>{__("is2or_vendor_payout.detail_section_request")}</h6>
                                            <table class="is2or-payout-detail-table">
                                                <tr>
                                                    <td>{__("comment")}</td>
                                                    <td>
                                                        {if $runtime.company_id}
                                                            {if $payout.comments}{$payout.comments}{else}-{/if}
                                                        {else}
                                                            <textarea class="span6"
                                                                    rows="3"
                                                                    cols="25"
                                                                    name="payout_comments[{$payout.payout_id}]"
                                                                    id="payout_comments_{$payout.payout_id}" disabled>{strip}{$payout.comments}{/strip}</textarea>
                                                        {/if}
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>

                                        {* ── Kolom kanan: Detail by Admin (receipt) ── *}
                                        <div class="is2or-payout-detail-section" style="flex:1; min-width:0;">
                                            <h6>{__("is2or_vendor_payout.detail_section_admin")}</h6>
                                            {if $payout.is2or_receipt.comment}
                                                {assign var="r" value=$payout.is2or_receipt}
                                                <table class="is2or-payout-detail-table">
                                                    {if $payout.is2or_receipt.bank_name}
                                                        <tr>
                                                            <td>{__("is2or_vendor_payout.receipt_bank_name")}</td>
                                                            <td>{$r.bank_name|default:"-"}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>{__("is2or_vendor_payout.account_name")}</td>
                                                            <td>{$r.account_name|default:"-"}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>{__("is2or_vendor_payout.iban")}</td>
                                                            <td>{$r.iban|default:"-"}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>{__("vendor_payouts.transaction_value")}</td>
                                                            <td>
                                                                {if $r.transaction_value}
                                                                    {include file="common/price.tpl" value=$r.transaction_value}
                                                                {else}
                                                                    -
                                                                {/if}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>{__("is2or_vendor_payout.receipt_transaction_date")}</td>
                                                            <td>
                                                                {if $r.created_at}
                                                                    {$r.created_at|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                                                                {else}
                                                                    -
                                                                {/if}
                                                            </td>
                                                        </tr>
                                                    {/if}
                                                    <tr>
                                                        <td>{__("comment")}</td>
                                                        <td>{$r.comment|default:"-"}</td>
                                                    </tr>
                                                    {if $r.receipt_image}
                                                        <tr>
                                                            <td>{__("is2or_vendor_payout.receipt_image")}</td>
                                                            <td>
                                                                <a href="{$r.receipt_image_url}" target="_blank">
                                                                    <img
                                                                        src="{$r.receipt_image_url}"
                                                                        class="is2or-receipt-image-thumb"
                                                                        style="max-width:300px; max-height:300px; object-fit:contain;"
                                                                        alt="{__("is2or_vendor_payout.receipt_image")}"
                                                                    />
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    {/if}
                                                </table>
                                            {else}
                                                <span class="is2or-no-receipt">{__("is2or_vendor_payout.no_receipt_yet")}</span>
                                            {/if}
                                        </div>

                                    </div>{* /.is2or-payout-detail-wrap *}
                                </td>
                            </tr>
                        {/foreach}
                    <!--payouts_list--></table>
                </div>
            {/capture}

            {include file="common/context_menu_wrapper.tpl"
                form="manage_payouts_form"
                object="payouts"
                items=$smarty.capture.payouts_table
            }
        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}

        <div class="clearfix">
            {include file="common/pagination.tpl"}
        </div>

        {if $payouts && $totals}
            {include file="views/companies/components/balance_info.tpl"}
        {/if}
    </form>
    {capture name="buttons"}
        {if !$hide_controls && $payouts}
            {include file="buttons/save.tpl" but_name="dispatch[companies.update_payout_comments]" but_role="action" but_target_form="manage_payouts_form" but_meta="cm-submit nav__actions-btn-save"}
        {/if}
    {/capture}

    {capture name="adv_buttons"}
        {hook name="companies:balance_adv_buttons"}
            {if $is_allow_add_payout}
                {capture name="add_new_picker"}
                    {include file="views/companies/components/balance_new_payment.tpl" c_url=$c_url}
                {/capture}
                {if $runtime.company_id}
                    {$popup_title = __("new_withdrawal")}
                    {$btn_title = __("add_withdrawal")}
                {else}
                    {$popup_title = __("new_payout")}
                    {$btn_title = __("add_payout")}
                {/if}
                {include file="common/popupbox.tpl"
                    id="add_payment"
                    text=$popup_title
                    content=$smarty.capture.add_new_picker
                    title=$btn_title
                    link_text=$btn_title
                    act="general"
                    icon="icon-plus"
                    link_class="btn-primary nav__actions-btn-primary"
                }
            {/if}
        {/hook}
    {/capture}

    {capture name="sidebar"}
        {include file="common/saved_search.tpl" dispatch="companies.balance" view_type="balance"}
        {include file="views/companies/components/balance_search_form.tpl" dispatch="companies.balance"}
    {/capture}

{/capture}
{capture name="mainbox_title"}
    {__("vendor_accounting")}
    {if $current_balance}
        {capture name="balance"}
            {include file="common/price.tpl" value=$current_balance}
        {/capture}
        <span class="f-middle">{__("vendor_payouts.current_balance", ["[balance]" => $smarty.capture.balance])}</span>
    {/if}
{/capture}
{include file="common/mainbox.tpl"
         title=$smarty.capture.mainbox_title
         content=$smarty.capture.mainbox
         buttons=$smarty.capture.buttons
         adv_buttons=$smarty.capture.adv_buttons
         sidebar=$smarty.capture.sidebar
}
