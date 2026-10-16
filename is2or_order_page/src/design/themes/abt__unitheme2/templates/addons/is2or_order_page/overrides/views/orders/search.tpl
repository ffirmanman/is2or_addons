{** IS2OR Override: views/orders/search.tpl **}
{** New Order Page Design **}

<link rel="stylesheet" type="text/css" href="{$config.current_location}/design/themes/abt__unitheme2/css/addons/is2or_order_page/orders.css" />

{** Status mapping for tabs **}
{assign var="tab_statuses" value=[
    'all'        => [],
    'waiting'    => ['O', 'N'],
    'processing' => ['P', 'Y', 'A'],
    'shipping'   => ['E', 'B'],
    'delivered'  => ['G'],
    'successful' => ['C'],
    'failed'     => ['F', 'D', 'I'],
    'refund'     => ['H']
]}

{assign var="tab_labels" value=[
    'all'        => 'All',
    'waiting'    => 'Waiting for Payment',
    'processing' => 'Processed by Seller',
    'shipping'   => 'Shipping',
    'delivered'  => 'Delivered',
    'successful' => 'Successful',
    'failed'     => 'Failed',
    'refund'     => 'Return & Refund'
]}

{** Status display mapping **}
{assign var="status_labels" value=[
    'O' => 'Waiting for Payment',
    'N' => 'Waiting for Payment',
    'P' => 'Paid',
    'Y' => 'Awaiting Call',
    'A' => 'Packing',
    'E' => 'Shipping',
    'B' => 'Backordered',
    'G' => 'Delivered',
    'C' => 'Successful',
    'F' => 'Failed',
    'D' => 'Declined',
    'I' => 'Canceled',
    'H' => 'Refunded'
]}

{assign var="status_classes" value=[
    'O' => 'waiting',
    'N' => 'waiting',
    'P' => 'processing',
    'Y' => 'processing',
    'A' => 'processing',
    'E' => 'shipping',
    'B' => 'shipping',
    'G' => 'delivered',
    'C' => 'successful',
    'F' => 'failed',
    'D' => 'failed',
    'I' => 'failed',
    'H' => 'refund'
]}

{** Get active tab from URL or default to 'all' **}
{assign var="active_tab" value=$smarty.request.is2or_tab|default:"all"}

{** Get search query and date filter **}
{assign var="search_query" value=$smarty.request.is2or_search|default:""}
{assign var="filter_date" value=$smarty.request.is2or_date|default:""}

<div class="is2or-orders-page">

    <h1 class="is2or-orders-page__title">{__("my_orders")}</h1>

    {** Search & Filter Bar **}
    <div class="is2or-orders-toolbar">
        <div class="is2or-orders-search">
            <i class="ty-icon-search is2or-orders-search__icon"></i>
            <input type="text"
                   id="is2or_search_input"
                   class="is2or-orders-search__input"
                   placeholder="{__("find_your_order")}"
                   value="{$search_query}"
                   oninput="is2orFilterOrders()" />
        </div>
        <div class="is2or-orders-filter">
            <i class="ty-icon-calendar is2or-orders-filter__icon"></i>
            <input type="date"
                   id="is2or_date_filter"
                   class="is2or-orders-filter__input"
                   placeholder="{__("filter_by_order_date")}"
                   value="{$filter_date}"
                   onchange="is2orFilterOrders()" />
        </div>
    </div>

    {** Tabs **}
    <div class="is2or-orders-tabs" id="is2or_tabs">
        {foreach $tab_labels as $tab_key => $tab_label}
            {** Count orders for this tab **}
            {assign var="tab_count" value=0}
            {if $tab_key == 'all'}
                {assign var="tab_count" value=$orders|count}
            {else}
                {foreach $orders as $o}
                    {if in_array($o.status, $tab_statuses.$tab_key)}
                        {assign var="tab_count" value=$tab_count+1}
                    {/if}
                {/foreach}
            {/if}

            <button type="button"
                    class="is2or-orders-tab {if $tab_key == $active_tab}is2or-orders-tab--active{/if}"
                    onclick="is2orSwitchTab('{$tab_key}')">
                {$tab_label}
            </button>
        {/foreach}
    </div>

    {** Orders List **}
    <div class="is2or-orders-list" id="is2or_orders_list">
        {if $orders}
            {foreach $orders as $o}
                {assign var="order_tab" value="all"}
                {foreach $tab_statuses as $tk => $statuses}
                    {if $tk != 'all' && in_array($o.status, $statuses)}
                        {assign var="order_tab" value=$tk}
                    {/if}
                {/foreach}

                {assign var="status_label" value=$status_labels[$o.status]|default:$o.status}
                {assign var="status_class" value=$status_classes[$o.status]|default:"default"}

                <div class="is2or-order-card"
                     data-tab="{$order_tab}"
                     data-order-id="{$o.order_id}"
                     data-date="{$o.timestamp|date_format:"Y-m-d"}"
                     data-search="{$o.order_id} {$o.company|lower}">

                    {** Card Header **}
                    <div class="is2or-order-card__header">
                        <div class="is2or-order-card__vendor">
                            <i class="ty-icon-basket is2or-order-card__vendor-icon"></i>
                            {$o.company|default:__("store")}
                        </div>
                        <div class="is2or-order-card__status is2or-order-card__status--{$status_class}">
                            {$status_label}
                        </div>
                    </div>

                    {** Products **}
                    <div class="is2or-order-products">
                        {if $o.products}
                            {foreach $o.products as $product}
                                <div class="is2or-order-product">
                                    {** Image **}
                                    <div class="is2or-order-product__image">
                                        {if $product.main_pair}
                                            {include file="common/image.tpl"
                                                images=$product.main_pair
                                                obj_id=$product.product_id
                                                image_width=60
                                                image_height=60
                                                lazy_load=false}
                                        {else}
                                            <span class="ty-no-image" style="width:60px;height:60px;">
                                                <span class="ty-icon ty-icon-image ty-no-image__icon"></span>
                                            </span>
                                        {/if}
                                    </div>

                                    {** Info **}
                                    <div class="is2or-order-product__info">
                                        <a href="{"products.view?product_id=`$product.product_id`"|fn_url}"
                                           class="is2or-order-product__name">
                                            {$product.product nofilter}
                                        </a>
                                        {if $product.extra_parsed.product_options}
                                            {foreach $product.extra_parsed.product_options as $opt_id => $opt_val}
                                                <div class="is2or-order-product__variant">
                                                    {$opt_val.option_name}: {$opt_val.variant_name}
                                                </div>
                                            {/foreach}
                                        {/if}
                                        <div class="is2or-order-product__qty">x{$product.amount}</div>
                                    </div>

                                    {** Price **}
                                    <div class="is2or-order-product__price">
                                        {include file="common/price.tpl" value=$product.price*$product.amount}
                                    </div>
                                </div>
                            {/foreach}
                        {/if}
                    </div>

                    {** Card Footer **}
                    <div class="is2or-order-card__footer">
                        <div class="is2or-order-card__footer-left">
                            {__("ordered")}: {$o.timestamp|date_format:"%a, %B %d %Y %H:%M"}
                        </div>
                        <div class="is2or-order-card__footer-right">
                            <div class="is2or-order-card__total">
                                {__("total")}: <strong>{include file="common/price.tpl" value=$o.total}</strong>
                            </div>

                            {** More button **}
                            <div class="is2or-order-more" id="is2or_more_{$o.order_id}">
                                <button type="button"
                                        class="is2or-order-btn is2or-order-btn--outline"
                                        onclick="is2orToggleMore({$o.order_id})">
                                    {__("more")} ▾
                                </button>
                                <div class="is2or-order-more__dropdown" id="is2or_more_dd_{$o.order_id}">
                                    <a href="{"orders.details?order_id=`$o.order_id`"|fn_url}">
                                        {__("order_details")}
                                    </a>
                                    <a href="{"orders.print_invoice?order_id=`$o.order_id`"|fn_url}" target="_blank">
                                        {__("print_invoice")}
                                    </a>
                                    <a href="{"products.search?search_performed=Y&order_ids=`$o.order_id`"|fn_url}">
                                        {__("search_products")}
                                    </a>
                                </div>
                            </div>

                            {** Request Return/Refund **}
                            {if $o.status == 'C' || $o.status == 'G'}
                                <a href="{"orders.details?order_id=`$o.order_id`"|fn_url}"
                                   class="is2or-order-btn is2or-order-btn--outline">
                                    {__("request_return_refund")}
                                </a>
                            {/if}

                            {** Confirm Order **}
                            {if $o.status == 'G'}
                                <a href="{"orders.details?order_id=`$o.order_id`"|fn_url}"
                                   class="is2or-order-btn is2or-order-btn--outline">
                                    {__("confirm_order")}
                                </a>
                            {/if}

                            {** Order Again **}
                            <a href="{"orders.reorder?order_id=`$o.order_id`"|fn_url}"
                               class="is2or-order-btn is2or-order-btn--primary">
                                {__("order_again")}
                            </a>
                        </div>
                    </div>

                </div>
            {/foreach}
        {/if}

        {** Empty state **}
        <div class="is2or-orders-empty" id="is2or_empty_state" style="display:none;">
            <div class="is2or-orders-empty__icon">📦</div>
            <div class="is2or-orders-empty__text">{__("text_no_orders")}</div>
        </div>

    </div>
    {** END orders list **}

</div>

<script>
var is2orActiveTab = '{$active_tab}';

function is2orSwitchTab(tab) {
    is2orActiveTab = tab;

    // Update tab buttons
    document.querySelectorAll('.is2or-orders-tab').forEach(function(btn) {
        btn.classList.remove('is2or-orders-tab--active');
    });
    event.currentTarget.classList.add('is2or-orders-tab--active');

    is2orFilterOrders();
}

function is2orFilterOrders() {
    var searchQuery = document.getElementById('is2or_search_input').value.toLowerCase();
    var dateFilter = document.getElementById('is2or_date_filter').value;
    var cards = document.querySelectorAll('.is2or-order-card');
    var visibleCount = 0;

    cards.forEach(function(card) {
        var tab = card.getAttribute('data-tab');
        var orderDate = card.getAttribute('data-date');
        var searchData = card.getAttribute('data-search').toLowerCase();

        // Tab filter
        var tabMatch = (is2orActiveTab === 'all' || tab === is2orActiveTab);

        // Search filter
        var searchMatch = !searchQuery || searchData.indexOf(searchQuery) !== -1;

        // Date filter
        var dateMatch = !dateFilter || orderDate === dateFilter;

        if (tabMatch && searchMatch && dateMatch) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    // Show/hide empty state
    var emptyState = document.getElementById('is2or_empty_state');
    if (emptyState) {
        emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

function is2orToggleMore(orderId) {
    var dropdown = document.getElementById('is2or_more_dd_' + orderId);
    var isVisible = dropdown.style.display === 'block';

    // Close all dropdowns
    document.querySelectorAll('.is2or-order-more__dropdown').forEach(function(dd) {
        dd.style.display = 'none';
    });

    if (!isVisible) {
        dropdown.style.display = 'block';
    }
}

// Close more dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.is2or-order-more')) {
        document.querySelectorAll('.is2or-order-more__dropdown').forEach(function(dd) {
            dd.style.display = 'none';
        });
    }
});

// Run filter on page load
document.addEventListener('DOMContentLoaded', function() {
    is2orFilterOrders();
});
</script>
