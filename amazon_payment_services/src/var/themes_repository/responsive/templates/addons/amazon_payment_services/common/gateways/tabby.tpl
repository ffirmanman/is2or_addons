{$cart = $smarty.session.cart}
{$phone_number = $cart.user_data.b_phone|default:$cart.user_data.phone|default:$cart.user_data.s_phone|default:""}

<div class="ty-control-group">
    <label for="aps_tabby_phone_number" class="ty-control-group__title cm-required">{__("aps_phone_number")}</label>
    <input
        id="aps_tabby_phone_number"
        type="text"
        name="payment_data[aps][tabby][phone_number]"
        value="{$phone_number}"
        class="fld cm-required cm-autocomplete-off"
        autocomplete="off"
        placeholder="{__("aps_phone_number_placeholder")}"
        maxlength="19"
        {if !$is_active}disabled="disabled"{/if}
    />
    <p class="ty-control-group__description">{__("aps_tabby_phone_number_hint")}</p>
</div>
