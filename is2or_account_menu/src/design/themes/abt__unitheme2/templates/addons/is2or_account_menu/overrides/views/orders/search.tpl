{** IS2OR Override: views/orders/search.tpl **}
{** New Order Page Design **}

<link rel="stylesheet" type="text/css" href="{$config.current_location}/design/themes/abt__unitheme2/css/addons/is2or_account_menu/style.css" />
<link rel="stylesheet" type="text/css" href="{$config.current_location}/design/themes/abt__unitheme2/css/addons/is2or_account_menu/orders.css" />

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


<div class="ty-vc-profile-layout">

    <div class="ty-vc-profile-layout__menu">
        {include file="addons/is2or_account_menu/components/account_menu.tpl"}
    </div>
    <div class="ty-vc-profile-layout__content">
        <div class="is2or-orders-page">
            <h1 class="is2or-orders-page__title">{__("my_orders")}</h1>
            {** Search & Filter Bar **}
            <div class="is2or-orders-toolbar">
                <div class="is2or-orders-search">
                    <input type="text"
                        id="is2or_search_input"
                        class="is2or-orders-search__input"
                        placeholder="       {__("find_your_order_number")}"
                        value="{$search_query}"
                        oninput="is2orFilterOrders()" />
                    <i class="ty-icon-search is2or-orders-search__icon"></i>
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
                            onclick="is2orSwitchTab('{$tab_key}', this)">
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
                            data-date="{$o.timestamp|date_format:"%Y-%m-%d"}"
                            data-search="{$o.order_id} {$o.company|lower}">

                            {** Top meta row **}
                            <div class="is2or-order-card__top">
                                <div class="is2or-order-card__meta">
                                    <div class="is2or-order-card__meta-item">
                                        <span class="is2or-order-card__meta-label">{__("order_placed")|upper}</span>
                                        <span class="is2or-order-card__meta-value">{$o.timestamp|date_format:"%b %d, %Y"}</span>
                                    </div>
                                    <div class="is2or-order-card__meta-item">
                                        <span class="is2or-order-card__meta-label">{__("total")|upper}</span>
                                        <span class="is2or-order-card__meta-value">{include file="common/price.tpl" value=$o.total}</span>
                                    </div>
                                    <div class="is2or-order-card__meta-item">
                                        <span class="is2or-order-card__meta-label">{__("ship_to")|upper}</span>
                                        <span class="is2or-order-card__meta-value">{$o.firstname} {$o.lastname}</span>
                                    </div>
                                </div>

                                <div class="is2or-order-card__meta-right">
                                    <span class="is2or-order-card__meta-label">{__("order")|upper} #{$o.order_id}</span>
                                    <a href="{"orders.details?order_id=`$o.order_id`"|fn_url}" class="is2or-order-card__details-link">
                                        {__("order_details")}
                                    </a>
                                </div>
                            </div>

                            {** Status line **}
                            <div class="is2or-order-card__status-line is2or-order-card__status-line--{$status_class}">
                                <i class="is2or-status-icon is2or-status-icon--{$status_class}"></i>
                                <span>{$status_label}{if $order_tab == 'delivered'} {$o.timestamp|date_format:"%b %d"}{/if}</span>
                            </div>

                            {** Body: product image(s) + action button **}
                            <div class="is2or-order-card__body">
                                <div class="is2or-order-products">
                                    {if $o.products}
                                        {foreach $o.products as $product}
                                            <div class="is2or-order-product">
                                                <div class="is2or-order-product__image">
                                                    {if $product.main_pair}
                                                        {include file="common/image.tpl"
                                                            images=$product.main_pair
                                                            obj_id=$product.product_id
                                                            image_width=80
                                                            image_height=80
                                                            lazy_load=false}
                                                    {else}
                                                        <span class="ty-no-image" style="width:80px;height:80px;">
                                                            <span class="ty-icon ty-icon-image ty-no-image__icon"></span>
                                                        </span>
                                                    {/if}
                                                </div>
                                            </div>
                                        {/foreach}
                                    {/if}
                                </div>

                                <div class="is2or-order-card__action">
                                    {if $status_class == 'shipping'}
                                        <a href="{"orders.details?order_id=`$o.order_id`"|fn_url}"
                                        class="is2or-order-btn is2or-order-btn--primary">
                                            {__("track_order")}
                                        </a>
                                    {elseif $status_class == 'delivered' || $status_class == 'successful'}
                                        <a href="{"orders.reorder?order_id=`$o.order_id`"|fn_url}"
                                        class="is2or-order-btn is2or-order-btn--primary">
                                            {__("order_again")}
                                        </a>
                                    {elseif $status_class == 'failed' || $status_class == 'refund'}
                                        <a href="{"orders.details?order_id=`$o.order_id`"|fn_url}"
                                        class="is2or-order-btn is2or-order-btn--primary">
                                            {__("view_details")}
                                        </a>
                                    {else}
                                        <a href="{"orders.details?order_id=`$o.order_id`"|fn_url}"
                                        class="is2or-order-btn is2or-order-btn--primary">
                                            {__("order_details")}
                                        </a>
                                    {/if}
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
    </div>
</div>

<style>
    .is2or-orders-search__input:focus + .is2or-orders-search__icon,
    .is2or-orders-search__input:not(:placeholder-shown) + .is2or-orders-search__icon {
        display: none;
    }
</style>

<script>
var is2orActiveTab = '{$active_tab}';

function is2orSwitchTab(tab, btnEl) {
    is2orActiveTab = tab;

    // Update tab buttons
    document.querySelectorAll('.is2or-orders-tab').forEach(function(btn) {
        btn.classList.remove('is2or-orders-tab--active');
    });
    if (btnEl) {
        btnEl.classList.add('is2or-orders-tab--active');
    }

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

        var tabMatch = (is2orActiveTab === 'all' || tab === is2orActiveTab);
        var searchMatch = !searchQuery || searchData.indexOf(searchQuery) !== -1;
        var dateMatch = !dateFilter || orderDate === dateFilter;

        if (tabMatch && searchMatch && dateMatch) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

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
