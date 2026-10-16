{if $id}
<div id="content_size_chart">
    {if $size_charts}
        {foreach from=$size_charts item="size_chart"}
        <div class="control-group">
            <label class="control-label">{__("is2or_size_charts.applied_size_chart")}:</label>
            <div class="controls">
                <span class="shift-input">
                    <a href="{fn_url("size_charts.update&id=`$size_chart.chart_id`")}" target="_blank">{$size_chart.title}</a>
                </span>
            </div>
        </div>
            <div class="control-group">
                <label class="control-label">{__("is2or_size_charts.sample_chart")}:</label>
                <div class="controls">
                    {include file="addons/is2or_size_charts/views/size_charts/components/sample_chart.tpl" chart=$size_chart.chart}
                </div>
            </div>
        {/foreach}
    {else}
        <p>{__("is2or_size_charts.no_size_chart_applies")}</p>
    {/if}
</div>
{/if}