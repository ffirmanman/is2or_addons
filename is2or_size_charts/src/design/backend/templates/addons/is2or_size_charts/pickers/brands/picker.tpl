{math equation="rand()" assign="rnd"}
{$data_id=$picker_id|default:"{$data_id}_{$rnd}"}
{$view_mode=$view_mode|default:"mixed"}
{$show_but_text=$show_but_text|default:"true"}

{script src="js/tygh/picker.js"}

{if $item_ids && !$item_ids|is_array}
    {$item_ids=","|explode:$item_ids}
{/if}

{$display=$display|default:"checkbox"}

{if $view_mode != "list" && $view_mode != "single_button"}

    {if $extra_var}
        {$extra_var=$extra_var|escape:url}
    {/if}

    {if $placement == 'right'}
        <div class="clearfix">
            <div class="pull-right">
    {/if}

    {if $show_but_text}
        {$but_text=$_but_text}
    {else}
        {$but_text=""}
    {/if}

    {include file="buttons/button.tpl"
        but_id="opener_picker_`$data_id`"
        but_href="sc_brands.picker?display=`$display`&extra=`$extra_var`&picker_for=`$picker_for`&data_id=`$data_id`&shared_force=`$shared_force``$extra_url`"|fn_url
        but_text=__("is2or_size_charts.add_brands")
        but_role="add"
        but_target_id="content_`$data_id`"
        but_meta="cm-dialog-opener btn"
        but_icon="icon-plus"
    }

    {if $placement == 'right'}
        </div></div>
    {/if}

{/if}

{if $view_mode == "single_button"}

{elseif $view_mode != "button"}
    {if $display != "radio"}
        <input id="u{$data_id}_ids" type="hidden" name="{$input_name}" value="{if $item_ids}{","|implode:$item_ids}{/if}" />

        <div class="clearfix"></div>
        <div class="table-responsive-wrapper">
            <table width="100%" class="table table-middle table--relative table-responsive table-responsive-w-titles">
            <thead>
            <tr>
                <th width="40%">{__("feature")}</th>
                <th width="60%">{__("brand")}</th>
                <th>&nbsp;</th>
            </tr>
            </thead>
            <tbody id="{$data_id}"{if !$item_ids} class="hidden"{/if}>
            {include
                file="addons/is2or_size_charts/pickers/brands/js.tpl"
                brand_id="`$ldelim`brand_id`$rdelim`"
                brand="`$ldelim`brand`$rdelim`"
                feature="`$ldelim`feature`$rdelim`"
                holder=$data_id
                clone=true
            }
            {if $item_ids}
            {foreach from=$item_ids item="brand_id" name="items"}
                {$brand = fn_is2or_size_charts_get_brand($brand_id)}
                {include
                    file="addons/is2or_size_charts/pickers/brands/js.tpl"
                    brand_id=$brand.brand_id
                    brand=$brand.brand
                    feature=$brand.feature
                    holder=$data_id
                    first_item=$smarty.foreach.items.first
                }
            {/foreach}
            {/if}
            </tbody>
            <tbody id="{$data_id}_no_item"{if $item_ids} class="hidden"{/if}>
            <tr class="no-items">
                <td colspan="3" data-th="&nbsp;"><p>{$no_item_text|default:__("no_items") nofilter}</p></td>
            </tr>
            </tbody>
            </table>
        </div>
    {/if}
{/if}

{if $view_mode != "list"}
    <div class="hidden" id="content_{$data_id}" title="{$_but_text}">
    </div>
{/if}