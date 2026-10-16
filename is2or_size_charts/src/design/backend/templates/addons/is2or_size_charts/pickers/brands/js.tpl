{if $view_mode == "list" || $view_mode == "mixed"}
    <tr {if !$clone}id="{$holder}_{$brand_id}" {/if}class="cm-js-item{if $clone} cm-clone hidden{/if}">
        <td data-th="&nbsp;">{$feature}</td>
        <td data-th="&nbsp;">{$brand}</td>
        <td class="nowrap" data-th="&nbsp;">
            {if !$view_only}
                {capture name="tools_list"}
                    <li>{btn type="list" text=__("remove") onclick="Tygh.$.cePicker('delete_js_item', '{$holder}', '{$brand_id}', 'u'); return false;"}</li>
                {/capture}
                <div class="hidden-tools">
                    {dropdown content=$smarty.capture.tools_list}
                </div>
            {/if}
        </td>
    </tr>
{/if}