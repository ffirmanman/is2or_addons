<table class="table table-middle">
    <thead class="cm-first-sibling">
    <tr>
        <th class="no-wrap">{__("position")}</th>
        <th class="no-wrap">{__("size")}</th>
        {foreach from=$template.columns item="tpl_row"}
        <th class="no-wrap">{$tpl_row.title}</th>
        {/foreach}
        <th>&nbsp;</th>
    </tr>
    </thead>
    {foreach from=$data['variants'] item="vrow" key="k" name="rdf"}
        <tr class="cm-row-item">
            <td>
                <input type="text" name="data[variants][{$k}][position]" size="5" value="{$vrow.position|default:0}" class="input-micro" />
            </td>
            <td>
                <input type="text" name="data[variants][{$k}][__name]" value="{$vrow.__name}"  />
            </td>
            {foreach from=$template.columns item="tpl_row"}
                {$val = ''}
                {if $vrow[$tpl_row.code]}{$val = $vrow[$tpl_row.code]}{/if}
            <td>
                <input type="text" name="data[variants][{$k}][{$tpl_row.code}]" size="5" value="{$val}" class="input-mini" />
            </td>
            {/foreach}
            <td class="nowrap right">
                <div class="hidden-tools">
                    {include file="buttons/remove_item.tpl" only_delete='Y' but_class="cm-delete-row"}
                </div>
            </td>
        </tr>
    {/foreach}
    {assign var="_key" value=$k|default:0+10}
    <tr id="box_add_rate_celm_weight">
        <td>
            <input type="text" name="data[variants][{$_key}][position]" size="5" value="0" class="input-micro" />
        </td>
        <td>
            <input type="text" name="data[variants][{$_key}][__name]" value=""  />
        </td>
        {foreach from=$template.columns item="tpl_row"}
        <td>
            <input type="text" name="data[variants][{$_key}][{$tpl_row.code}]" size="5" value="" class="input-mini" />
        </td>
        {/foreach}
        <td class="right">
            <div class="hidden-tools">{include file="buttons/multiple_buttons.tpl" item_id="add_rate_celm_weight"}</div>
        </td>
    </tr>
</table>