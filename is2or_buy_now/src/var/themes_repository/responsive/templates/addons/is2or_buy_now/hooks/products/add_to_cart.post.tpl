{if $details_page || $selected_layout == 'products_without_options'}
    <a href="{"checkout.buy_now?product_id=`$product.product_id`"|fn_url}" class="cm-post ty-btn ty-btn__secondary">{__('buy_now')}</a>
{/if}