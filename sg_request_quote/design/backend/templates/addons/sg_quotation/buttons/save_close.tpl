{if $but_target_id || $but_target_form}
{assign var="but_role" value="submit-link"}
{else}
{assign var="but_role" value="button_main"}
{/if}

{if $save}
    {assign var="but_label" value=__("save")}
    {assign var="but_label2" value=__("save_and_close")}
{else}
    {assign var="but_label" value=__("create")}
    {assign var="but_label2" value=__("create_and_close")}
{/if}

{if $but_name}{assign var="r" value=$but_name}{else}{assign var="r" value=$but_href}{/if}

{if $cancel_action == "close"}
    <a class="cm-dialog-closer cm-inline-dialog-closer cm-cancel tool-link btn {$cancel_meta}">{__("close")}</a>
{/if}

{if $r|fn_check_view_permissions}
    {if !$hide_first_button}
        {if $approve}
            {include file="buttons/button.tpl" but_text=$approve_text|default:$but_label but_onclick=$but_onclick but_role=$but_role but_name=$approve_dispatch but_meta="btn-primary `$but_meta`"}
        {/if}
        
        {if $disapprove}
            {* {include file="buttons/button.tpl" but_text=$disapprove_text|default:$but_label but_onclick=$but_onclick but_role=$but_role but_name=$disapprove_dispatch but_meta="btn-primary `$but_meta`"} *}
            {include file="common/popupbox.tpl"
                id="quotation_disaprove_`$quote.quotation_id`"
                act="edit"
                link_class="cm-dialog-destroy-on-close btn btn-primary cm-ajax cm-dialog-closer"
                link_text=__("disapprove")
                text=__("disapprove")
                href="sg_quotation.disaprove?id={$quotationID}&status=D"
            }
        {/if}
        
    {else}
        {assign var="skip_or" value=true}
    {/if}
{else}
    {assign var="skip_or" value=true}
{/if}

{if $extra}
    {$extra nofilter}
{/if}
