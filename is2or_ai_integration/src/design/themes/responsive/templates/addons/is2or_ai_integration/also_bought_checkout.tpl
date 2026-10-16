{if $products}

    {assign var="tmpl" value="products_multicolumns"}
    {counter name="is2or_also_bought_counter" start=1 print=false assign="is2or_counter"}
    {assign var="is2or_uid" value="is2or-also-bought-`$is2or_counter`"}

    <div class="is2or-also-bought" id="{$is2or_uid}">

        {* Header *}
        <div class="is2or-also-bought__header">
            <div class="is2or-also-bought__header-icon">
                <i class="ty-icon-star"></i>
            </div>

            <div class="is2or-also-bought__header-content">
                <h3 class="is2or-also-bought__title">
                    {__("is2or_ai_integration.people_also_bought")}
                </h3>

                <div class="is2or-also-bought__subtitle">
                    {__("is2or_ai_integration.also_bought_products_description")}
                </div>
            </div>

            <div class="is2or-also-bought__dots"></div>
            <div class="is2or-also-bought__wave"></div>
        </div>

        {* Viewport (scroll area, edge fade + floating arrows) *}
        <div class="is2or-also-bought__viewport">

            <button type="button" class="is2or-also-bought__edge-btn is2or-also-bought__edge-btn--prev" aria-label="{__("previous")}">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none">
                    <path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            {* Products *}
            <div class="is2or-also-bought__grid">

                {foreach from=$products item="product"}

                    <div class="is2or-also-bought__card">

                        {* Product image *}
                        <div class="is2or-also-bought__image-wrapper">

                            <a href="{"products.view?product_id=`$product.product_id`"|fn_url}"
                               class="is2or-also-bought__image">

                                {include file="common/image.tpl"
                                    images=$product.main_pair
                                    image_type="M"
                                    no_image_v="ty-no-image"
                                }

                            </a>
                        </div>

                        {* Product body *}
                        <div class="is2or-also-bought__body">

                            <div class="is2or-also-bought__info">

                                {* Product name *}
                                <a href="{"products.view?product_id=`$product.product_id`"|fn_url}"
                                   class="is2or-also-bought__name">

                                    {$product.product}

                                </a>

                                {* Vendor *}
                                {if $product.company_name}

                                    <div class="is2or-also-bought__vendor">

                                        {__("sold_by")}:

                                        <a href="{"companies.products?company_id=`$product.company_id`"|fn_url}">
                                            {$product.company_name}
                                        </a>

                                    </div>

                                {/if}

                                {* Price *}
                                <div class="is2or-also-bought__price">

                                    {include file="common/price.tpl"
                                        value=$product.price
                                        show_login_link=true
                                    }

                                </div>

                            </div>

                            {* Stock + Add to cart *}
                            <div class="is2or-also-bought__buttons">

                                {if $product.amount > 0}
                                    <div class="is2or-also-bought__stock">
                                        <span class="is2or-also-bought__stock-dot"></span>
                                        In stock
                                    </div>
                                {/if}

                                {include file="blocks/list_templates/simple_list.tpl"
                                    product=$product
                                    tmpl=$tmpl
                                    min_qty=false
                                    show_qty=false
                                    show_add_to_cart=true
                                    show_discount_label=false
                                    show_rating_num=false
                                    show_rating=false
                                    show_name=false
                                    show_price=false
                                    hide_compare_list_button=false
                                    hide_wishlist_button=false
                                    hide_quick_view_button=true
                                    but_role="action"
                                }

                            </div>

                        </div>

                    </div>

                {/foreach}

            </div>

            <button type="button" class="is2or-also-bought__edge-btn is2or-also-bought__edge-btn--next" aria-label="{__("next")}">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none">
                    <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

        </div>

    </div>


<style>
/* =========================================================
   IS2OR - Also Bought Products
   ========================================================= */

.is2or-also-bought {
    position: relative;
    margin: 20px 0;
    padding: 12px 18px 18px;

    background: #fff;

    border: 3px solid rgba(255, 95, 46, 0.38);
    border-radius: 14px;

    overflow: hidden;
    max-width: 100%;
}


/* =========================================================
   Header
   ========================================================= */

.is2or-also-bought__header {
    position: relative;

    display: flex;
    align-items: center;

    min-height: 82px;

    margin: 0 0 20px;
    padding: 16px 22px;

    background: linear-gradient(
        110deg,
        #fff7f3 0%,
        #fff0eb 55%,
        #ffe2d8 100%
    );

    border-radius: 14px;

    overflow: hidden;
}


/* Header icon */

.is2or-also-bought__header-icon {
    position: relative;
    z-index: 2;

    display: flex;
    align-items: center;
    justify-content: center;

    width: 56px;
    height: 56px;

    margin-right: 16px;

    background: linear-gradient(
        145deg,
        #ff7a45,
        #f4511e
    );

    border-radius: 50%;

    box-shadow: 0 6px 16px rgba(244, 81, 30, 0.20);
}


.is2or-also-bought__header-icon i {
    color: #fff;
    font-size: 24px;
}


/* Header content */

.is2or-also-bought__header-content {
    position: relative;
    z-index: 2;
    flex: 1 1 auto;
    min-width: 0;
}


.is2or-also-bought__title {
    margin: 0 0 4px;

    color: #171717;

    font-size: 22px;
    font-weight: 700;
    line-height: 1.2;
}


.is2or-also-bought__subtitle {
    color: #6b6b6b;

    font-size: 13px;
    line-height: 1.35;
}


/* Carousel nav (header) */

.is2or-also-bought__nav-group {
    position: relative;
    z-index: 2;

    display: flex;
    gap: 8px;

    margin-left: 14px;
    flex: 0 0 auto;
}

.is2or-also-bought__nav,
.is2or-also-bought__edge-btn {
    display: flex;
    align-items: center;
    justify-content: center;

    width: 40px;
    height: 40px;

    background: #fff;
    color: #f4511e;

    border: none;
    border-radius: 50%;

    cursor: pointer;

    box-shadow: 0 3px 9px rgba(244, 81, 30, .15);

    transition:
        background .2s ease,
        color .2s ease,
        transform .15s ease,
        box-shadow .2s ease;
}

.is2or-also-bought__nav:hover,
.is2or-also-bought__edge-btn:hover {
    background: linear-gradient(145deg, #ff7a45, #f4511e);
    color: #fff;

    transform: translateY(-1px);

    box-shadow: 0 6px 16px rgba(244, 81, 30, .25);
}

.is2or-also-bought__nav:active,
.is2or-also-bought__edge-btn:active {
    transform: translateY(0) scale(.94);
}

.is2or-also-bought__nav[disabled],
.is2or-also-bought__edge-btn[disabled] {
    opacity: .35;
    cursor: default;
    box-shadow: none;
}

.is2or-also-bought__nav[disabled]:hover {
    background: #fff;
    color: #f4511e;
    transform: none;
    box-shadow: 0 3px 9px rgba(244, 81, 30, .15);
}


/* Decorative dots */

.is2or-also-bought__dots {
    position: absolute;

    top: 18px;
    right: 24px;

    width: 70px;
    height: 60px;

    opacity: .45;

    background-image: radial-gradient(
        circle,
        #ffb39a 3px,
        transparent 4px
    );

    background-size: 18px 18px;

    pointer-events: none;
}


/* Decorative wave */

.is2or-also-bought__wave {
    position: absolute;

    right: -50px;
    bottom: -55px;

    width: 420px;
    height: 110px;

    border-top: 3px solid rgba(255, 120, 80, .18);
    border-radius: 50%;

    transform: rotate(-8deg);

    pointer-events: none;
}


.is2or-also-bought__wave:before,
.is2or-also-bought__wave:after {
    content: "";

    position: absolute;

    right: 20px;

    width: 390px;
    height: 95px;

    border-top: 2px solid rgba(255, 120, 80, .12);
    border-radius: 50%;
}


.is2or-also-bought__wave:before {
    top: 10px;
}


.is2or-also-bought__wave:after {
    top: 20px;
}


/* =========================================================
   Carousel viewport (scroll area + edge fade + floating arrows)
   ========================================================= */

.is2or-also-bought__viewport {
    position: relative;
}

.is2or-also-bought__viewport::before,
.is2or-also-bought__viewport::after {
    content: "";

    position: absolute;
    top: 0;
    bottom: 0;

    width: 42px;
    z-index: 2;

    pointer-events: none;
}

.is2or-also-bought__viewport::before {
    left: 0;
    background: linear-gradient(90deg, #fff, rgba(255, 255, 255, 0));
}

.is2or-also-bought__viewport::after {
    right: 0;
    background: linear-gradient(270deg, #fff, rgba(255, 255, 255, 0));
}

.is2or-also-bought__edge-btn {
    position: absolute;
    top: 38%;
    transform: translateY(-50%);
    z-index: 3;

    width: 38px;
    height: 38px;

    box-shadow: 0 4px 14px rgba(0, 0, 0, .12);

    opacity: 0;
    visibility: hidden;

    transition:
        opacity .2s ease,
        visibility .2s ease,
        transform .15s ease,
        background .2s ease,
        color .2s ease;
}

.is2or-also-bought__edge-btn--prev { left: 2px; }
.is2or-also-bought__edge-btn--next { right: 2px; }

.is2or-also-bought:hover .is2or-also-bought__edge-btn {
    opacity: 1;
    visibility: visible;
}

.is2or-also-bought__edge-btn[disabled] {
    opacity: 0 !important;
    visibility: hidden !important;
}


/* =========================================================
   Product Grid / Carousel track (default: 4 cards visible)
   ========================================================= */

.is2or-also-bought__grid {
    display: grid;
    grid-auto-flow: column;
    grid-auto-columns: calc((100% - 3 * 12px) / 4);
    gap: 12px !important;

    overflow-x: auto;
    overflow-y: hidden;

    scroll-snap-type: x mandatory;
    scroll-behavior: smooth;

    padding: 10px 2px 5px 2px;

    -ms-overflow-style: none;
    scrollbar-width: none;

    cursor: grab;
    -webkit-user-select: none;
    user-select: none;
    margin: 10px 50px 10px 50px;
}

.is2or-also-bought__grid::-webkit-scrollbar {
    display: none;
}

.is2or-also-bought__grid.is2or-dragging {
    cursor: grabbing;
    scroll-snap-type: none;
    scroll-behavior: auto;
}

/* Cegah drag gambar/link bawaan browser mengganggu proses drag-scroll */
.is2or-also-bought__grid.is2or-dragging .is2or-also-bought__card,
.is2or-also-bought__grid.is2or-dragging a,
.is2or-also-bought__grid.is2or-dragging img {
    pointer-events: none;
}


/* =========================================================
   Product Card
   ========================================================= */

.is2or-also-bought__card {
    position: relative;

    display: flex;
    flex-direction: column;

    min-width: 0;

    scroll-snap-align: start;

    background: #fff;

    border: 1px solid #e8e8e8;
    border-radius: 12px;

    overflow: hidden;

    box-shadow:
        0 3px 10px rgba(0, 0, 0, 0.04),
        0 8px 22px rgba(0, 0, 0, 0.04);

    transition:
        transform .2s ease,
        box-shadow .2s ease,
        border-color .2s ease;
}


.is2or-also-bought__card:hover {
    transform: translateY(-3px);

    border-color: rgba(255, 95, 46, .25);

    box-shadow:
        0 6px 16px rgba(0, 0, 0, .06),
        0 14px 30px rgba(255, 95, 46, .08);
}


/* =========================================================
   Product Image
   ========================================================= */

.is2or-also-bought__image-wrapper {
    position: relative;

    margin: 6px 6px 0;

    overflow: hidden;

    border-radius: 10px;

    background: #f7f8fa;
}


.is2or-also-bought__image {
    display: block;

    width: 100%;

    /* Lebih pendek dibanding sebelumnya */
    aspect-ratio: 1.45 / 1;

    overflow: hidden;

    background: #f7f8fa;
}


.is2or-also-bought__image img {
    display: block;

    width: 100%;
    height: 100%;

    object-fit: cover;

    transition: transform .3s ease;
}


.is2or-also-bought__card:hover .is2or-also-bought__image img {
    transform: scale(1.03);
}


/* =========================================================
   Favorite Button
   ========================================================= */

.is2or-also-bought__favorite {
    position: absolute;

    top: 9px;
    right: 9px;

    z-index: 5;

    display: flex;
    align-items: center;
    justify-content: center;

    width: 34px;
    height: 34px;

    background: rgba(255, 255, 255, .96);

    border-radius: 50%;

    color: #ff5f2e;

    box-shadow: 0 3px 9px rgba(0, 0, 0, .10);

    text-decoration: none;

    transition:
        transform .2s ease,
        background .2s ease;
}


.is2or-also-bought__favorite:hover {
    background: #fff;

    color: #f4511e;

    transform: scale(1.08);

    text-decoration: none;
}


.is2or-also-bought__favorite i {
    font-size: 17px;
}


/* =========================================================
   Product Body
   ========================================================= */

.is2or-also-bought__body {
    display: flex;
    flex-direction: column;
    flex: 1;

    padding: 14px 14px 14px;
}


.is2or-also-bought__info {
    flex: 1;
}


/* Product name */

.is2or-also-bought__name {
    display: block;

    margin-bottom: 6px;

    color: #171717;

    font-size: 14px;
    font-weight: 700;
    line-height: 1.3;

    text-decoration: none;

    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}


.is2or-also-bought__name:hover {
    color: #ff5f2e;

    text-decoration: none;
}


/* =========================================================
   Vendor
   ========================================================= */

.is2or-also-bought__vendor {
    margin-bottom: 12px;

    color: #777;

    font-size: 12px;
    line-height: 1.35;
}


.is2or-also-bought__vendor a {
    color: #ff5f2e;

    text-decoration: none;
}


.is2or-also-bought__vendor a:hover {
    text-decoration: underline;
}


/* =========================================================
   Price
   ========================================================= */

.is2or-also-bought__price {
    margin-bottom: 14px;

    color: #ff5f2e;

    font-size: 19px;
    font-weight: 700;
}


.is2or-also-bought__price .ty-price {
    color: #ff5f2e;
    font-weight: 700;
}


/* =========================================================
   Bottom Section
   ========================================================= */

.is2or-also-bought__buttons {
    position: relative;

    padding-top: 12px;

    border-top: 1px solid #eeeeee;
}


/* Stock badge */

.is2or-also-bought__stock {
    display: inline-flex;
    align-items: center;

    margin-bottom: 12px;
    padding: 5px 10px;

    background: #edf9f1;

    border-radius: 30px;

    color: #249447;

    font-size: 11px;
    font-weight: 600;
}


.is2or-also-bought__stock-dot {
    width: 6px;
    height: 6px;

    margin-right: 6px;

    background: #24a148;

    border-radius: 50%;
}


/* =========================================================
   CS-Cart Add To Cart
   ========================================================= */

.is2or-also-bought__buttons .ty-btn {
    display: flex;
    align-items: center;
    justify-content: center;

    width: 100%;
    min-height: 40px;

    margin: 0;

    padding: 8px 14px;

    background: #f4511e;

    border: none;
    border-radius: 24px;

    color: #fff;

    font-size: 13px;
    font-weight: 700;

    box-shadow: none;

    transition:
        background .2s ease,
        transform .2s ease,
        box-shadow .2s ease;
}


.is2or-also-bought__buttons .ty-btn:hover {
    background: #e94a1b;

    color: #fff;

    transform: translateY(-1px);

    box-shadow: 0 5px 13px rgba(244, 81, 30, .20);
}


.is2or-also-bought__buttons .ty-btn i {
    margin-right: 6px;

    font-size: 16px;
}


/* =========================================================
   Responsive
   ========================================================= */

@media (max-width: 979px) {

    .is2or-also-bought__grid {
        grid-auto-columns: calc((100% - 2 * 12px) / 3);
    }

}


@media (max-width: 767px) {

    .is2or-also-bought {
        padding: 8px 10px 14px;

        border-width: 3px;
        border-radius: 12px;
    }


    .is2or-also-bought__header {
        min-height: 72px;

        margin-bottom: 16px;
        padding: 12px 14px;

        border-radius: 12px;
    }


    .is2or-also-bought__header-icon {
        width: 46px;
        height: 46px;

        margin-right: 12px;
    }


    .is2or-also-bought__header-icon i {
        font-size: 20px;
    }


    .is2or-also-bought__title {
        font-size: 19px;
    }


    .is2or-also-bought__subtitle {
        font-size: 12px;
    }


    .is2or-also-bought__dots,
    .is2or-also-bought__wave {
        display: none;
    }


    .is2or-also-bought__nav-group {
        margin-left: 8px;
    }

    .is2or-also-bought__nav {
        width: 34px;
        height: 34px;
    }


    .is2or-also-bought__grid {
        grid-auto-columns: calc((100% - 12px) / 2);

        gap: 12px;
    }

    /* Swipe manual di layar kecil, sembunyikan panah melayang */
    .is2or-also-bought__edge-btn {
        display: none;
    }

}


@media (max-width: 480px) {

    .is2or-also-bought__grid {
        grid-auto-columns: 78%;
    }

}


@media (min-width: 768px) and (max-width: 979px) {

    .is2or-also-bought__body {
        padding: 12px;
    }

}

.is2or-also-bought__buttons { margin-top: auto; }
.is2or-also-bought__buttons .ty-btn__add-to-cart,
.is2or-also-bought__buttons button {
    width: 100%;
}

.is2or-also-bought__buttons .ut2-simple-list__control.icon_button .ty-btn__add-to-cart {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
    padding: 0 12px;
    height: 20px;
    border-radius: 999px;
    margin-left:-10px;
}

.is2or-also-bought__buttons .ut2-simple-list__control.icon_button .ty-btn__add-to-cart span::after {
    content: "Add to cart";
    color: #fff;
    font-weight: 600;
    font-size: 12px;
    white-space: nowrap;
}

.is2or-also-bought__buttons .ut2-simple-list__control.icon_button .ty-btn__add-to-cart i {
    font-size: 14px;
}

.is2or-also-bought .ty-qty-in-stock {
    display: none !important;
}

@media (max-width: 767px) {
    .is2or-also-bought {
        max-width: 100%;
    }
}

.is2or-also-bought .ty-qty.clearfix.changer {
    margin: -65px 15px 0 20px !important;
    padding: 0 20px !important;
}
</style>


<script>
    (function () {
        var root = document.getElementById("{$is2or_uid}");
        if (!root) { return; }

        var grid = root.querySelector(".is2or-also-bought__grid");
        if (!grid) { return; }

        var navBtns = root.querySelectorAll(
            ".is2or-also-bought__nav--prev, .is2or-also-bought__edge-btn--prev, " +
            ".is2or-also-bought__nav--next, .is2or-also-bought__edge-btn--next"
        );

        function step() {
            var card = grid.querySelector(".is2or-also-bought__card");
            if (!card) { return grid.clientWidth * 0.8; }

            var style = window.getComputedStyle(grid);
            var gap = parseFloat(style.columnGap || style.gap || 0) || 12;

            return card.getBoundingClientRect().width + gap;
        }

        function visibleCount() {
            var s = step();
            if (!s) { return 1; }
            return Math.max(1, Math.round(grid.clientWidth / s));
        }

        function scrollByCards(direction) {
            grid.scrollBy({ left: direction * step() * visibleCount(), behavior: "smooth" });
        }

        function updateButtons() {
            var maxScroll = grid.scrollWidth - grid.clientWidth - 1;
            var atStart = grid.scrollLeft <= 0;
            var atEnd = grid.scrollLeft >= maxScroll;

            root.querySelectorAll(".is2or-also-bought__nav--prev, .is2or-also-bought__edge-btn--prev")
                .forEach(function (b) { b.disabled = atStart; });

            root.querySelectorAll(".is2or-also-bought__nav--next, .is2or-also-bought__edge-btn--next")
                .forEach(function (b) { b.disabled = atEnd; });
        }

        navBtns.forEach(function (btn) {
            var isPrev = btn.className.indexOf("--prev") !== -1;
            btn.addEventListener("click", function () {
                scrollByCards(isPrev ? -1 : 1);
            });
        });

        grid.addEventListener("scroll", function () {
            window.requestAnimationFrame(updateButtons);
        }, { passive: true });

        window.addEventListener("resize", updateButtons);
        updateButtons();

        /* ===== Click & drag to scroll (mouse) ===== */
        var isDown = false;
        var didDrag = false;
        var startX = 0;
        var startScroll = 0;

        function dragStart(e) {
            isDown = true;
            didDrag = false;
            grid.classList.add("is2or-dragging");
            startX = e.pageX - grid.offsetLeft;
            startScroll = grid.scrollLeft;
        }

        function dragEnd() {
            if (!isDown) { return; }
            isDown = false;
            grid.classList.remove("is2or-dragging");
        }

        function dragMove(e) {
            if (!isDown) { return; }
            e.preventDefault();

            var x = e.pageX - grid.offsetLeft;
            var walk = x - startX;

            /* Anggap sebagai drag (bukan klik) setelah bergerak lebih dari 5px */
            if (Math.abs(walk) > 5) { didDrag = true; }

            grid.scrollLeft = startScroll - walk;
        }

        grid.addEventListener("mousedown", dragStart);
        grid.addEventListener("mouseleave", dragEnd);
        grid.addEventListener("mouseup", dragEnd);
        grid.addEventListener("mousemove", dragMove);

        /* Cegah klik ke link produk terpicu tepat setelah drag selesai */
        grid.addEventListener("click", function (e) {
            if (didDrag) {
                e.preventDefault();
                e.stopPropagation();
                didDrag = false;
            }
        }, true);
    })();
</script>

{/if}
