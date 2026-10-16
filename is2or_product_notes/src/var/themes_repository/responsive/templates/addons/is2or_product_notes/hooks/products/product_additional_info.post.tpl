{if fn_product_notes_is_allow_notes($product.product_id) === "YesNo::YES"|enum}
    <div class="is2or-product-notes__wrapper">
        {$product_notes = $product.product_id|fn_product_notes_get_notes:"cart"}

        <div class="ty-control-group ty-product-options__item product-list-field clearfix">
            <label class="ty-control-group__label ty-product-options__item-label">{__('notes')}:</label>
            <textarea class="product-notes__input" data-ca-item-id="{$product.product_id}" rows="3">{$product_notes}</textarea>
        </div>
    </div>
{/if}