{$paid = $addons.is2or_management_order_status.paid_status}
{if $view_only != 'Y' && $order_info.status == $paid}
    {include file="buttons/button.tpl"
        but_role="text"
        but_text=__("is2or_management_order_status.cancel_order")
        but_href="is2or_management_order_status.cancel_order?order_id=`$order_info.order_id`"
        but_meta="ty-btn__text cm-confirm cm-post"
    }
{/if}
