<h4>{$chart.title}</h4>

{if $chart['recommendation']}
    <p>{__('is2or_size_charts.recommended_size', ['[size]' => $chart['recommendation']['__name']])}</p>
{/if}

{if $show_texts}
<div>{$chart.text_before nofilter}</div>
{/if}

<table class="ty-table" style="min-width: 300px; margin-top: 0;">
    <thead>
        <tr>
            <th>{__('size')}</th>
            {foreach from=$chart.headers item="h"}
            <th style="text-align: center;">{$h}</th>
            {/foreach}
        </tr>
    </thead>

    <tbody>
        {foreach from=$chart.rows item="r"}
        <tr>
            {foreach from=$r item="c" key="k"}
            <td {if $k !== '__name'}style="text-align: center;"{/if}>{$c}</td>
            {/foreach}
        </tr>
        {/foreach}
    </tbody>
</table>

{if $show_texts}
<div style="margin-top:10px; margin-bottom:10px;">{$chart.text_after nofilter}</div>
{/if}