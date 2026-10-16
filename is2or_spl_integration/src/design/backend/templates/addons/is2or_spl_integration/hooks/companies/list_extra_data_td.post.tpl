<td>
    {assign var="spl_v_status" value=$company.spl_verification_status|default:"pending"}
    {if $spl_v_status == "verified"}
        <span class="label label-success">✓</span>
    {elseif $spl_v_status == "failed"}
        <span class="label label-important">✗</span>
    {elseif $spl_v_status == "overridden"}
        <span class="label label-warning">~</span>
    {else}
        <span class="label">?</span>
    {/if}
</td>
