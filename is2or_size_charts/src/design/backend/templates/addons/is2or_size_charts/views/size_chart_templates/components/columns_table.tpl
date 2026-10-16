<table class="table table-middle">
    <thead class="cm-first-sibling">
    <tr>
        <th class="no-wrap">{__("position")}</th>
        <th class="no-wrap">{__("is2or_size_charts.code")}</th>
        <th class="no-wrap">{__("is2or_size_charts.title")}</th>
        <th>&nbsp;</th>
    </tr>
    </thead>
    {foreach from=$data['columns'] item="v" key="k" name="rdf"}
        <tr class="cm-row-item">
            <td>
                <input type="text" name="data[columns][{$k}][position]" size="5" value="{$v.position|default:0}" class="input-micro" />
            </td>
            <td>
                <input type="text" name="data[columns][{$k}][code]" size="5" value="{$v.code}" class="input-medium" />
            </td>
            <td>
                <input type="text" name="data[columns][{$k}][title]" size="5" value="{$v.title}" class="input-medium" />
            </td>
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
            <input type="text" name="data[columns][{$_key}][position]" size="5" value="0" class="input-micro" />
        </td>
        <td>
            <input type="text" name="data[columns][{$_key}][code]" size="5" value="" class="input-medium" />
        </td>
        <td>
            <input type="text" name="data[columns][{$_key}][title]" size="5" value="" class="input-medium" />
        </td>
        <td class="right">
            <div class="hidden-tools">{include file="buttons/multiple_buttons.tpl" item_id="add_rate_celm_weight"}</div>
        </td>
    </tr>
</table>