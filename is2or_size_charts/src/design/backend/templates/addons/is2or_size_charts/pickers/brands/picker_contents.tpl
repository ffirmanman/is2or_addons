{if !$smarty.request.extra}
<script>
(function(_, $) {
    _.tr('text_items_added', '{__("text_items_added")|escape:"javascript"}');
    var display_type = '{$smarty.request.display|escape:javascript nofilter}';

    $.ceEvent('on', 'ce.formpost_add_users_form', function(frm, elm) {
        var users = {};

        if ($('input.cm-item:checked', frm).length > 0) {

            $('input.cm-item:checked', frm).each( function() {
                var id = $(this).val();
                var item = $(this).parent().siblings();

                if (display_type == 'radio') {
                    users[id] = item.find('.brand').text()
                } else {
                    users[id] = {
                        brand: item.find('.brand').text(),
                        feature: item.find('.feature').text()
                    };
                }
            });

            if (display_type == 'radio') {
                {literal}
                $.cePicker('add_js_item', frm.data('caResultId'), users, 'u', {
                    '{brand_id}': '%id',
                    '{brand}': '%item.brand',
                    '{feature}': '%item.feature'
                });
                {/literal}
            } else {
                {literal}
                $.cePicker('add_js_item', frm.data('caResultId'), users, 'u', {
                    '{brand_id}': '%id',
                    '{brand}': '%item.brand',
                    '{feature}': '%item.feature'
                });
                {/literal}

                $.ceNotification('show', {
                    type: 'N',
                    title: _.tr('notice'),
                    message: _.tr('text_items_added'),
                    message_state: 'I'
                });
            }
        }

        return false;
    });
}(Tygh, Tygh.$));
</script>
{/if}

{include
    file="addons/is2or_size_charts/pickers/brands/search_form.tpl"
    dispatch="sc_brands.picker"
    extra="<input type=\"hidden\" name=\"result_ids\" value=\"pagination_`$smarty.request.data_id|escape:'html'`\">"
    put_request_vars=true
    form_meta="cm-ajax"
    in_popup=true
}

<form action="{$smarty.request.extra|fn_url}" method="post" data-ca-result-id="{$smarty.request.data_id}" name="add_users_form">

{include file="common/pagination.tpl" save_current_page=true div_id="pagination_`$smarty.request.data_id`"}

{if $brands}
<div class="table-responsive-wrapper">
    <table width="100%" class="table table-middle table--relative table-responsive">
    <thead>
    <tr>
        <th width="1%" class="center">
            {if $smarty.request.display == "checkbox"}
            {include file="common/check_items.tpl"}</th>
            {/if}
        <th>{__("is2or_size_charts.brand_id")}</th>
        <th>{__("feature")}</th>
        <th>{__("brand")}</th>
    </tr>
    </thead>
    {foreach from=$brands item="brand"}
    <tr>
        <td class="left" data-th="">
            {if $smarty.request.display == "checkbox"}
            <input type="checkbox" name="add_brands[]" value="{$brand.brand_id}" class="cm-item" />
            {elseif $smarty.request.display == "radio"}
            <input type="radio" name="selected_brand_id" class="cm-item" value="{$brand.brand_id}" />
            {/if}
        </td>
        <td data-th="{__("id")}">{$brand.brand_id}</td>
        <td data-th="{__("feature")}"><span class="feature">{$brand.feature}</span></td>
        <td data-th="{__("brand")}"><span class="brand">{$brand.brand}</span></td>
    </tr>
    {/foreach}
    </table>
</div>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl" div_id="pagination_`$smarty.request.data_id`"}

<div class="buttons-container">
    {if $smarty.request.display == "radio"}
        {assign var="but_close_text" value=__("choose")}
    {else}
        {assign var="but_close_text" value=__("is2or_size_charts.add_brands_and_close")}
        {assign var="but_text" value=__("is2or_size_charts.add_brands")}
    {/if}

    {include file="buttons/add_close.tpl" is_js=$smarty.request.extra|fn_is_empty}
</div>

</form>
