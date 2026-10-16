{if $addons.is2or_size_charts.show_in_product_page=="L1"}
    {$show_texts=true}
    {if $addons.is2or_size_charts.show_in_product_page_type=='table' && $addons.is2or_size_charts.show_in_table_mode_texts=='N'}
        {$show_texts=false}
    {/if}
    <div class="ty-expiry-date-group">
        <span class="ty-control-group__label product-list-field">&nbsp;</span>
        <span class="ty-control-group__item">
        {include file="addons/is2or_size_charts/components/size_chart.tpl" is_popup=($addons.is2or_size_charts.show_in_product_page_type=="link")}
        </span>
    </div>
{/if}
