{$columns=5}

{if !$wishlist_is_empty}
    {script src="js/tygh/exceptions.js"}
    {assign var="show_hr" value=false}
    {assign var="location" value="cart"}
{/if}

<link type="text/css" rel="stylesheet" href="{"design/themes/abt__unitheme2/css/addons/is2or_account_menu/style.css"|fn_url}" />

<div class="ty-vc-profile-layout">

    <div class="ty-vc-profile-layout__menu">
        {include file="addons/is2or_account_menu/components/account_menu.tpl"}
    </div>

    <div class="ty-vc-profile-layout__content">
        {$tmpl='products_multicolumns'}

        {assign var="low_stock_threshold" value=5}

        {if $products}
            <div class="wl-grid" id="wlGrid">
                {foreach from=$products item="product"}
                    <div class="wl-card">
                        <a href="{"wishlist.delete?cart_id=`$product.cart_id`&redirect_url=`$config.current_url|escape:url`"|fn_url}"
                            class="wl-card__delete"
                            title="{__("remove")}">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                        </a>

                        <a href="{"products.view?product_id=`$product.product_id`"|fn_url}" class="wl-card__image">
                            {include file="common/image.tpl" images=$product.main_pair image_type="M" no_image_v="ty-no-image"}
                        </a>

                        <div class="wl-card__body">
                            <div class="wl-card__info">
                                <div class="wl-card__row">
                                    <a href="{"products.view?product_id=`$product.product_id`"|fn_url}" class="wl-card__name">{$product.product}</a>
                                </div>
                                <div class="wl-card__row">
                                    {if $product.amount > 0}
                                        {if $product.amount <= $low_stock_threshold}
                                            <span class="wl-card__stock wl-card__stock--low">{__("text_only_x_left_in_stock", ["[amount]" => $product.amount])|default:"Only {$product.amount} left"}</span>
                                        {else}
                                            <span class="wl-card__stock wl-card__stock--ok">{__("text_x_units_left_in_stock", ["[amount]" => $product.amount])|default:"{$product.amount} units left in stock"}</span>
                                        {/if}
                                    {/if}
                                </div>

                                {if $product.company_name}
                                    <div class="wl-card__vendor">
                                        {__("sold_by")}: <a href="{"companies.products?company_id=`$product.company_id`"|fn_url}">{$product.company_name}</a>
                                    </div>
                                {/if}

                                <div class="wl-card__price">
                                    {include file="common/price.tpl" value=$product.price show_login_link=true}
                                </div>
                            </div>

                            <div class="ty-wishlist-cards__buttons">
                                {include file="blocks/list_templates/simple_list.tpl"
                                    product=$product
                                    min_qty=false
                                    show_qty=false
                                    show_add_to_cart=true
                                    show_discount_label=false
                                    show_rating_num=false
                                    show_rating=false
                                    show_name=false
                                    show_price=false
                                    hide_compare_list_button=true
                                    hide_wishlist_button=true
                                    hide_quick_view_button=true
                                    but_role="action"}
                            </div>
                        </div>
                    </div>
                {/foreach}
            </div>

            <div class="wl-pagination" id="wlPagination"></div>
        {else}
            {math equation="100 / x" x=$columns|default:"5" assign="cell_width"}
            <div class="ty-grid-list{if $wishlist_is_empty} ty-wish-list-empty{/if}">
                {assign var="iteration" value=0}
                {capture name="iteration"}{$iteration}{/capture}
                    {hook name="wishlist:view"}
                    {/hook}
                {assign var="iteration" value=$smarty.capture.iteration}
                {if $iteration == 0 || $iteration % $columns != 0}
                    {math assign="empty_count" equation="c - it%c" it=$iteration c=$columns}
                    {section loop=$empty_count name="empty_rows"}
                        <div class="ty-column{$columns}">
                            <div class="ty-product-empty">
                                <span class="ty-product-empty__text">{__("empty")}</span>
                            </div>
                        </div>
                    {/section}
                {/if}
            </div>
        {/if}

        {if !$wishlist_is_empty}
            <div class="buttons-container ut2-wish-list__buttons">
                {include file="buttons/button.tpl" but_text=__("clear_wishlist") but_href="wishlist.clear" but_meta="ty-btn__outline"}
                {include file="buttons/continue_shopping.tpl" but_href=$continue_url|fn_url but_role="text"}
            </div>
        {else}
            <div class="buttons-container ut2-wish-list__buttons ty-wish-list__continue">
                {include file="buttons/continue_shopping.tpl" but_href=$continue_url|fn_url but_role="text"}
            </div>
        {/if}

        {capture name="mainbox_title"}{__("wishlist_content")}{/capture}

        
        
    </div>
</div>

<script>
(function() {
    var perPage = 10;
    var grid = document.getElementById('wlGrid');
    var pager = document.getElementById('wlPagination');
    var currentPage = 1;

    function getCards() {
        return Array.prototype.slice.call(grid.children);
    }

    function showPage(page) {
        var cards = getCards();
        var totalPages = Math.ceil(cards.length / perPage) || 1;

        if (page > totalPages) page = totalPages;
        currentPage = page;

        cards.forEach(function(card, i) {
            card.style.display = (i >= (page - 1) * perPage && i < page * perPage) ? '' : 'none';
        });

        renderPager(page, totalPages);
    }

    function renderPager(active, totalPages) {
        if (totalPages <= 1) { pager.innerHTML = ''; return; }
        var html = '';
        for (var p = 1; p <= totalPages; p++) {
            html += '<button class="wl-page-btn' + (p === active ? ' active' : '') + '" data-page="' + p + '">' + p + '</button>';
        }
        pager.innerHTML = html;
        pager.querySelectorAll('.wl-page-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                showPage(parseInt(this.dataset.page, 10));
                window.scrollTo({ top: grid.offsetTop - 100, behavior: 'smooth' });
            });
        });
    }

    if (getCards().length) showPage(1);

})();
</script>

<style>
/* ---------- My Wishlist card grid ---------- */
.wl-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 20px;
    align-items: stretch;
}

.wl-card {
    position: relative;
    border: 1px solid #eceef1;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    display: flex;
    flex-direction: column;
    height: 100%; /* card mengisi tinggi grid row */
}

.wl-card__delete {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: rgba(255, 255, 255, .9);
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
    text-decoration: none;
    transition: color .15s ease-in-out;
}
.wl-card__delete:hover {
    color: #dc2626;
}

.wl-card__image {
    display: block;
    height: 220px;          /* tinggi tetap, tidak pakai aspect-ratio lagi */
    flex-shrink: 0;         /* jangan sampai gambar ikut mengecil */
    background: #F8FAFC;
    overflow: hidden;
}
.wl-card__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.wl-card__body {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;                /* body mengisi sisa tinggi card */
}

.wl-card__info {
    flex: 1;                /* dorong button ke bawah */
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.wl-card__row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 8px;
}

.wl-card__name {
    font-weight: 600;
    font-size: 15px;
    color: #1a1a1a;
    text-decoration: none;
}
.wl-card__name:hover {
    text-decoration: underline;
}

.wl-card__stock {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    white-space: nowrap;
}
.wl-card__stock--ok {
    color: #16a34a;
}
.wl-card__stock--low {
    color: #ea580c;
}

.wl-card__vendor {
    font-size: 12px;
    color: #6b7280;
}
.wl-card__vendor a {
    color: #ff5f2e;
    text-decoration: none;
}
.wl-card__vendor a:hover {
    text-decoration: underline;
}

.wl-card__price {
    font-size: 20px;
    font-weight: 700;
    color: #ff5f2e;
    margin: 4px 0 8px;
}

.wl-card__cart-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #ff5f2e;
    color: #fff;
    font-weight: 600;
    font-size: 14px;
    padding: 12px 16px;
    border-radius: 8px;
    text-decoration: none;
    transition: opacity .15s ease-in-out;
    margin-top: auto;       /* kunci: selalu di paling bawah body */
}
.wl-card__cart-btn:hover {
    opacity: .9;
    color: #fff;
    text-decoration: none;
}

.wl-pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 24px;
}
.wl-page-btn {
    border: 1px solid #eceef1;
    background: #fff;
    color: #1a1a1a;
    border-radius: 8px;
    padding: 8px 14px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
}
.wl-page-btn.active {
    background: #ff5f2e;
    border-color: #ff5f2e;
    color: #fff;
}
.ty-wishlist-cards__buttons { margin-top: auto; }
.ty-wishlist-cards__buttons .ty-btn__add-to-cart,
.ty-wishlist-cards__buttons button {
    width: 100%;
}


.ty-wishlist-cards__buttons .ut2-simple-list__control.icon_button .ty-btn__add-to-cart {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: auto;
    padding: 0 20px;
    height: 44px;
    border-radius: 999px;
}

.ty-wishlist-cards__buttons .ut2-simple-list__control.icon_button .ty-btn__add-to-cart span::after {
    content: "Add to cart";
    color: #fff;
    font-weight: 600;
    font-size: 14px;
    white-space: nowrap;
}

.ut2-simple-list__control.icon_button [id^="add_to_cart_update_"] .ty-btn__add-to-cart span.ty-icon::after {
    content: none !important;
}

.ut2-simple-list__control.icon_button [id^="add_to_cart_update_"] .ty-btn__add-to-cart span.ty-icon::after {
    content: "Add to cart" !important;
    font-family: "OutfitRegular", sans-serif !important;
    color: #fff !important;
    font-weight: 600 !important;
    font-size: 14px !important;
    white-space: nowrap !important;
    margin-left: 6px !important;
    display: inline-block !important;
}
</style>
