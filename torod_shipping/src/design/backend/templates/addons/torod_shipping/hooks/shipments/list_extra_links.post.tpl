{$awb_url = $shipment.shipment_id|fn_torod_shipping_check_shipment}
{if $awb_url != ''}
    <li class="divider"></li>
    <li>
        {btn type="list" text=__("torod_print_label") class="cm-new-window" href="`$awb_url`"}
    </li>
{/if}
