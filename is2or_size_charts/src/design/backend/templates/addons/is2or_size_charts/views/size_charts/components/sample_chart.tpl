<h4>{$chart.title}</h4>
<div>{$chart.text_before nofilter}</div>
<table class="table">
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
<div style="margin-top:10px">{$chart.text_after nofilter}</div>