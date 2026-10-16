<label class="is2or-shipping-item {if $cart.chosen_shipping.{$shipping.group_key} == $shipping.shipping_id}is2or-shipping-item--active{/if}"
       onclick="is2orSelectShipping({$shipping.shipping_id}, {$shipping.group_key})">
    {if $shipping.image}
        <div class="is2or-shipping-item__logo">
            {include file="common/image.tpl"
                obj_id=$shipping.shipping_id
                images=$shipping.image
                image_width=40
                image_height=20}
        </div>
    {/if}
    <input type="radio"
           class="litecheckout__shipping-method__radio"
           id="sh_{$shipping.group_key}_{$shipping.shipping_id}"
           name="shipping_ids[{$shipping.group_key}]"
           value="{$shipping.shipping_id}"
           form="litecheckout_payments_form"
           {if $cart.chosen_shipping.{$shipping.group_key} == $shipping.shipping_id}checked{/if}
           data-ca-lite-checkout-element="shipping-method"
           data-ca-lite-checkout-is-address-required="{if $shipping.is_address_required == "Y"}true{else}false{/if}"
    />
    <div class="is2or-shipping-item__info">
        <div class="is2or-shipping-item__name">{$shipping.shipping}</div>
        {if $shipping.delivery_time}
            <div class="is2or-shipping-item__eta">{$shipping.delivery_time}</div>
        {/if}
    </div>
    <div class="is2or-shipping-item__price">
        {if $shipping.rate|floatval}
            {include file="common/price.tpl" value=$shipping.rate}
        {else}
            {__("free")}
        {/if}
    </div>
</label>