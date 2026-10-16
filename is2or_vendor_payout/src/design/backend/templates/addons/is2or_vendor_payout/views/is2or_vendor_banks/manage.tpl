<div class="control-toolbar">
    <div class="control-toolbar__btns">
        <div class="control-toolbar__btns-right">
            {btn type="text"
                id="add_account_`$id`"
                href="{"is2or_vendor_banks.add?company_id=`$id`"|fn_url}"
                text=__("is2or_vendor_payout.add_account")
                icon_first=true
                icon="icon-plus"
                class="btn cm-dialog-opener cm-dialog-destroy-on-close"
                data=["data-ca-dialog-title" => __("is2or_vendor_payout.add_account")]
            }
        </div>
    </div>
</div>

{if $vendor_banks}
<form action="{""|fn_url}" method="post" id="vendor_banks_form" name="vendor_banks_form">
<input type="hidden" name="company_id" value="{$id}" />

{include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id="pagination_contents" search=$vendor_banks_search}

{capture name="vendor_banks_table"}
    <div class="table-responsive-wrapper longtap-selection">
        <table class="table table-middle table--relative table-responsive">
            <thead
                data-ca-bulkedit-default-object="true"
                data-ca-bulkedit-component="defaultObject"
            >
                <tr>
                    <th width="1%" class="left mobile-hide">
                        <input type="checkbox"
                            class="bulkedit-toggler hide"
                            data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                            data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                        />
                    </th>
                    <th width="10%">{include file="common/table_col_head.tpl" search=$vendor_banks_search type="country"}</th>
                    <th width="15%">{include file="common/table_col_head.tpl" search=$vendor_banks_search type="bank_name" text=__("is2or_vendor_payout.bank_name")}</th>
                    <th width="15%">{include file="common/table_col_head.tpl" search=$vendor_banks_search type="account_name" text=__("is2or_vendor_payout.account_name")}</th>
                    {* <th width="10%">{include file="common/table_col_head.tpl" search=$vendor_banks_search type="account_number" text=__("is2or_vendor_payout.account_number")}</th> *}
                    {* <th width="10%">{include file="common/table_col_head.tpl" search=$vendor_banks_search type="swift_code" text=__("is2or_vendor_payout.swift_code")}</th> *}
                    <th width="15%">{include file="common/table_col_head.tpl" search=$vendor_banks_search type="iban" text=__("is2or_vendor_payout.iban")}</th>
                    <th width="6%">&nbsp;</th>
                    <th width="10%" class="right">{include file="common/table_col_head.tpl" search=$vendor_banks_search type="status"}</th>
                </tr>
            </thead>
            <tbody>
                {foreach $vendor_banks as $vendor_bank}
                <tr class="cm-row-status-{$vendor_bank.status|lower} cm-longtap-target"
                    data-ca-longtap-action="setCheckBox"
                    data-ca-longtap-target="input.cm-item"
                    data-ca-id="{$vendor_bank.id}"
                >
                    <td width="1%" class="left mobile-hide">
                        <input type="checkbox" name="vendor_bank_ids[]" value="{$vendor_bank.id}" class="cm-item cm-item-status-{$vendor_bank.status|lower} hide" />
                    </td>
                    <td data-th="{__('country')}">{$vendor_bank.country|fn_get_country_name}</td>
                    <td data-th="{__('is2or_vendor_payout.bank_name')}">{$vendor_bank.bank_name}</td>
                    <td data-th="{__('is2or_vendor_payout.account_name')}">{$vendor_bank.account_name}</td>
                    {* <td data-th="{__('is2or_vendor_payout.account_number')}">{$vendor_bank.account_number}</td> *}
                    {* <td data-th="{__('is2or_vendor_payout.swift_code')}">{$vendor_bank.swift_code}</td> *}
                    <td data-th="{__('is2or_vendor_payout.iban')}">{$vendor_bank.iban}</td>
                    <td>
                        {capture name="tools_list"}
                            <li>
                                {btn type="list"
                                    text=__("edit")
                                    class="cm-dialog-opener cm-dialog-destroy-on-close"
                                    href="is2or_vendor_banks.update?company_id=`$id`&id=`$vendor_bank.id`"
                                    data=[
                                        "data-ca-dialog-title" => __("is2or_vendor_payout.edit_account")
                                    ]
                                }
                            </li>
                            <li>
                                {btn type="list"
                                    class="cm-confirm text-error"
                                    text=__("delete")
                                    href="is2or_vendor_banks.delete?company_id=`$id`&id=`$vendor_bank.id`"
                                    method="POST"
                                }
                            </li>
                        {/capture}
                        <div class="hidden-tools">
                            {dropdown content=$smarty.capture.tools_list}
                        </div>
                    </td>
                    <td class="right" data-th="{__("status")}">
                        {include file="common/select_popup.tpl"
                            type="is2or_vendor_banks"
                            id=$vendor_bank.id
                            status=$vendor_bank.status
                            hidden=false
                            object_id_name="id"
                            table="is2or_vendor_banks"
                        }
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
{/capture}

{include file="common/context_menu_wrapper.tpl"
    form="vendor_banks_form"
    object="is2or_vendor_banks"
    items=$smarty.capture.vendor_banks_table
    has_permissions=$has_permission
    is_check_all_shown=true
}

{include file="common/pagination.tpl" div_id="pagination_contents" search=$vendor_banks_search}
</form>

{else}
    <p class="no-items">{__("no_data")}</p>
{/if}
