{$showAt = $addons.is2or_vendor_payout.show_confirm_button_status}
{if $view_only != 'Y' && $order_info.status == $showAt && $order_info.is2or_customer_completed == 'N'}
    {include file="buttons/button.tpl"
        but_role="text"
        but_text=__("is2or_vendor_payout.complete_order")
        but_href="is2or_vendor_payout.complete_order?order_id=`$order_info.order_id`"
        but_meta="ty-btn__text cm-confirm cm-post"
    }
{/if}
