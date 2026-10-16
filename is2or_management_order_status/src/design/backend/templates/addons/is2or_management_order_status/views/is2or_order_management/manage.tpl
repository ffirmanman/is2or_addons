{capture name="mainbox"}

    <div class="cm-j-tabs cm-track tabs">
        {* Definisikan daftar tab agar kode tidak berulang *}
        {$manage_tabs = [
        'incomplete'      => 'tab_incomplete',
        'paid'      => 'tab_paid',
        'packing'   => 'tab_packing',
        'shipping'  => 'tab_shipping',
        'delivered' => 'tab_delivered',
        'complete'  => 'tab_completed',
        'declined'  => 'tab_declined',
        'cancelled' => 'tab_cancelled',
        'return'    => 'tab_returned'
        ]}

        <ul class="nav nav-tabs">
            {foreach from=$manage_tabs item="tab_lang" key="tab_id"}
                {* Buat copy dari search agar tidak merusak data asli *}
                {$temp_search = $search}
                {$temp_search.selected_tab = $tab_id}

                {* Hapus page agar setiap pindah tab selalu kembali ke halaman 1 *}
                {$temp_search.page = ""}

                <li class="{if $active_tab == $tab_id}active{/if}">
                    {* Gabungkan menggunakan http_build_query *}
                    <a href="{"is2or_order_management.manage?`$temp_search|http_build_query`"|fn_url}">
                        {__("is2or_management_order_status.`$tab_lang`")}
                        {if $tab_counts.$tab_id > 0}
                            <span class="badge badge-info">{$tab_counts.$tab_id}</span>
                        {/if}
                    </a>
                </li>
            {/foreach}
        </ul>
    </div>
    {capture name="sidebar"}
        {hook name="orders:manage_sidebar"}

            {* 1. Kirim dispatch bersih tanpa query string *}
        {$dispatch_url = "is2or_order_management.manage"}

            {include file="common/saved_search.tpl"
            dispatch=$dispatch_url
            view_type="orders"}

            {include file="views/orders/components/orders_search_form.tpl"
            dispatch=$dispatch_url
            }

        {/hook}
    {/capture}
<form action="{""|fn_url}" method="post" target="_self" name="orders_list_form" id="orders_list_form" data-ca-is-multiple-submit-allowed="true">

    {include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}

    {$c_url=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
    {$rev=$smarty.request.content_id|default:"pagination_contents"}
    {$page_title=__("orders")}
    {$extra_status=$config.current_url|escape:"url"}
    {$notify_vendor = fn_allowed_for("MULTIVENDOR")}
    {$notify=true}
    {$notify_department=true}

    {if $orders}
        {capture name="orders_table"}
            <div class="table-responsive-wrapper longtap-selection">
                <table width="100%" class="table table-middle table--relative table-responsive table--show-checkbox table-manage-orders">
                    <thead class="thead--overflow-hidden" data-ca-bulkedit-default-object="true" data-ca-bulkedit-component="defaultObject">
                    <tr>
                        <th width="3%" class="left mobile-hide table__check-items-column table__check-items-column--show-checkbox">
                            <input type="checkbox"
                                   class="bulkedit-toggler hide"
                                   data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                   data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                            />
                        </th>
                        <th width="15%">
                            {include file="common/table_col_head.tpl" type="order_id" text=__("id")}
                        </th>
                        <th width="15%">
                            {include file="common/table_col_head.tpl" type="status"}
                        </th>
                        <th width="15%">
                            {include file="common/table_col_head.tpl" type="date"}
                        </th>
                        <th width="28%">
                            {include file="common/table_col_head.tpl" type="customer"}
                        </th>
                        <th width="14%">
                            {include file="common/table_col_head.tpl" text=__("phone")}
                        </th>
                        <th width="15%">
                            {__("shipping")}
                        </th>
                        <th class="mobile-hide">&nbsp;</th>
                        <th width="10%" class="right">
                            {include file="common/table_col_head.tpl" type="total"}
                        </th>

                    </tr>
                    </thead>
                    {foreach from=$orders item="o"}
                        {hook name="orders:order_row"}
                            <tr class="cm-longtap-target"
                                data-ca-longtap-action="setCheckBox"
                                data-ca-longtap-target="input.cm-item"
                                data-ca-id="{$o.order_id}"
                            >
                                <td width="3%" class="left mobile-hide table__check-items-cell table__check-items-cell--show-checkbox">
                                    <input type="checkbox" name="order_ids[]" value="{$o.order_id}" class="cm-item cm-item-status-{$o.status|lower}" /></td>
                                <td width="15%" data-th="{__("id")}">
                                    <a href="{"orders.details?order_id=`$o.order_id`"|fn_url}" class="underlined link--monochrome">{__("order")} <bdi>#{$o.order_id}</bdi></a>
                                    {if $order_statuses[$o.status].params.appearance_type == "I" && $o.invoice_id}
                                        <p class="muted">{__("invoice")} #{$o.invoice_id}</p>
                                    {elseif $order_statuses[$o.status].params.appearance_type == "C" && $o.credit_memo_id}
                                        <p class="muted">{__("credit_memo")} #{$o.credit_memo_id}</p>
                                    {/if}
                                    {include file="views/companies/components/company_name.tpl" object=$o}
                                </td>
                                <td width="15%" data-th="{__("status")}">
                                    <button type="button" class="btn btn-info o-status-{$o.status|lower} order-status">
                                        {$order_statuses.{$o.status}.description|default:$o.status}
                                    </button>
                                    {if $o.issuer_id}
                                        {if $o.issuer_name|trim}
                                            <p class="muted shift-left manager-order">{$o.issuer_name}</p>
                                        {else}
                                            <p class="muted shift-left manager-order">{$o.issuer_email}</p>
                                        {/if}
                                    {/if}
                                </td>
                                <td width="15%" class="nowrap" data-th="{__("date")}">{$o.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</td>
                                <td width="17%" data-th="{__("customer")}">
                                    {if $o.email}<a href="mailto:{$o.email|escape:url}" class="link--monochrome">@</a> {/if}
                                    {if $o.company}<p class="muted">{$o.company}</p>{/if}
                                    {if $o.user_type !== "UserTypes::CUSTOMER"|enum
                                    && $auth.user_type !== "UserTypes::ADMIN"|enum
                                    && $o.user_id !== $auth.user_id
                                    || !$o.user_id
                                    }
                                        {$o.lastname} {$o.firstname}
                                    {elseif $o.user_id}
                                        <a href="{"profiles.update?user_id=`$o.user_id`"|fn_url}" class="link--monochrome">{$o.lastname} {$o.firstname}</a>
                                    {/if}
                                </td>
                                <td width="14%" {if $o.phone}data-th="{__("phone")}"{/if}>{if $o.phone}<bdi><a href="tel:{$o.phone}" class="link--monochrome">{$o.phone}</a></bdi>{/if}</td>

                                <td width="15%" data-th="{__("shipping")}">
                                    {* Ambil informasi lengkap order termasuk data Logistics (type L) *}
                                    {$order_info = $o.order_id|fn_get_order_info}

                                    {if $order_info.shipping}
                                        {foreach from=$order_info.shipping item="shipping"}
                                            <div class="muted">
                                                <strong>{$shipping.shipping}</strong>
                                                {if $shipping.service_delivery_time}
                                                    <br><small><i class="icon-time"></i> {$shipping.service_delivery_time}</small>
                                                {/if}
                                            </div>
                                        {/foreach}
                                    {else}
                                        <span class="muted">-</span>
                                    {/if}
                                </td>
                                <td class="center" data-th="{__("tools")}">
                                    {capture name="tools_items"}
                                        <li>{btn type="list" href="orders.details?order_id=`$o.order_id`" text={__("view")}}</li>
                                    {/capture}
                                    <div class="hidden-tools">
                                        {dropdown content=$smarty.capture.tools_items}
                                    </div>
                                </td>

                                <td width="10%" class="right" data-th="{__("total")}">
                                    {include file="common/price.tpl" value=$o.total}
                                </td>
                            </tr>
                        {/hook}
                    {/foreach}
                </table>
            </div>
        {/capture}

        {include file="common/context_menu_wrapper.tpl"
        form="orders_list_form"
        object="orders"
        items=$smarty.capture.orders_table
        }
    {else}
        <p class="no-items">{__("no_data")}</p>
    {/if}

    {include file="common/pagination.tpl" div_id=$smarty.request.content_id}
</form>
{/capture}



{include file="common/mainbox.tpl"
title=$page_title
sidebar=$smarty.capture.sidebar
content=$smarty.capture.mainbox
buttons=$smarty.capture.buttons
adv_buttons=$smarty.capture.adv_buttons
content_id="manage_orders"
select_storefront=true
storefront_switcher_param_name="storefront_id"
selected_storefront_id=$selected_storefront_id
}
