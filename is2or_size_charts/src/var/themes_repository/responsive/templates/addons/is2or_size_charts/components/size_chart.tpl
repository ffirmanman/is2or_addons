{$charts = fn_is2or_size_charts_get_product_charts($product.product_id, $auth.user_id)}
{if $charts}
    {$is_popup = $is_popup|default:false}
    
    {if $is_popup}
        <a title="{__("size_chart")}"
            data-ca-target-id="size_chart_dialog_{$product.product_id}"
            class="cm-dialog-opener cm-dialog-auto-size"
            rel="nofollow"
        >
            <i class="ty-icon-docs"></i>
            {__("size_chart")}
        </a>

        <div class="hidden" id="size_chart_dialog_{$product.product_id}" title="{__("size_chart")}">
    {/if}
    
        {foreach from=$charts item=chart}
        {include file="addons/is2or_size_charts/components/size_chart_content.tpl" chart=$chart.chart}
        {/foreach}
    
    {if $is_popup}
            <div class="margin-bottom:30px;">&nbsp;</div>
        </div>
    {/if}
{/if}