{if $page_type == $smarty.const.PAGE_TYPE_BLOG}

{include file="common/subheader.tpl" title=__("blog") target="#blog_category"}
<div id="blog_category" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="elm_blog_category">{__("category")}:</label>
            <div class="controls">
                <select name="page_data[category_id]" id="elm_blog_category">
                    <option value="">--</option>
                    {foreach $blog_categories as $category}
                        <option value="{$category.category_id}" {if $page_data.category_id == $category.category_id}selected="selected"{/if}>{$category.category}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    </fieldset>
</div>

{/if}
