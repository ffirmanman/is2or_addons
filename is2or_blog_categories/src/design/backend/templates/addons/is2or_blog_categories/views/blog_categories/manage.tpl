{capture name="mainbox"}

<form action="{""|fn_url}" method="post" name="blog_categories_form" id="blog_categories_form">
{include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id="pagination_contents"}

{if $categories}
    {capture name="categories_table"}
        <div class="table-responsive-wrapper longtap-selection">
            <table class="table table-middle table--relative table-responsive">
            <thead
                data-ca-bulkedit-default-object="true"
                data-ca-bulkedit-component="defaultObject"
            >
            <tr>
                <th width="1%" class="left mobile-hide">
                    <input type="checkbox"
                        class="bulkedit-toggler hide"
                        data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                        data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                    />
                </th>
                <th width="50%">{include file="common/table_col_head.tpl" type="category"}</th>
                <th width="6%" class="mobile-hide">&nbsp;</th>
            </tr>
            </thead>
            {foreach from=$categories item=category}
            <tr class="cm-row-status-{$category.status|lower} cm-longtap-target"
                data-ca-longtap-action="setCheckBox"
                data-ca-longtap-target="input.cm-item"
                data-ca-id="{$category.category_id}"
            >
                <td width="1%" class="left mobile-hide">
                    <input type="checkbox" name="category_ids[]" value="{$category.category_id}" class="cm-item  cm-item-status-{$category.status|lower} hide" />
                </td>
                <td data-th="{__("category")}">
                    <input type="text" name="categories[{$category.category_id}][category]" value="{$category.category}" class="input-large input-hidden" />
                </td>
                </td>
                <td width="6%" class="mobile-hide">
                    {capture name="tools_list"}
                        <li>{btn type="list" class="cm-confirm text-error" text=__("delete") href="blog_categories.delete?category_id=`$category.category_id`" method="POST"}</li>
                    {/capture}
                    <div class="hidden-tools">
                        {dropdown content=$smarty.capture.tools_list}
                    </div>
                </td>
            </tr>
            {/foreach}
            </table>
        </div>
    {/capture}

    {include file="common/context_menu_wrapper.tpl"
        form="blog_categories_form"
        object="blog_categories"
        items=$smarty.capture.categories_table
        is_check_all_shown=true
    }
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl" div_id="pagination_contents"}
</form>

{capture name="buttons"}
    {if $categories}
        {include file="buttons/save.tpl" but_name="dispatch[blog_categories.m_update]" but_role="action" but_target_form="blog_categories_form" but_meta="cm-submit"}
    {/if}
{/capture}

{capture name="adv_buttons"}
    {capture name="add_new_category"}
        <form action="{""|fn_url}" method="post" class="form-horizontal form-edit" name="add_category_form">
            <fieldset>
                <div class="control-group">
                    <label class="control-label cm-required" for="c_category">{__("category")}</label>
                    <div class="controls">
                        <input class="span9" type="text" id="c_category" name="category_data[category]" value="" size="60" />
                    </div>
                </div>
            </fieldset>

            <div class="buttons-container">
                {include file="buttons/save_cancel.tpl" but_name="dispatch[blog_categories.update]" cancel_action="close"}
            </div>
        </form>
    {/capture}

    {include file="common/popupbox.tpl" id="add_new_source" text=__("is2or_blog_categories.create_category") title=__("is2or_blog_categories.create_category") act="general" content=$smarty.capture.add_new_category icon="icon-plus"}
{/capture}

{/capture}

{include file="common/mainbox.tpl"
    content=$smarty.capture.mainbox
    title=__('blog_categories')
    adv_buttons=$smarty.capture.adv_buttons
    buttons=$smarty.capture.buttons
}