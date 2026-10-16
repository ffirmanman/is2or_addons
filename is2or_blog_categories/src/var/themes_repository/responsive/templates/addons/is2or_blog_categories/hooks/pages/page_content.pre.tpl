{if $subpages}
    {assign var="c_url" value=$config.current_url|fn_query_remove:"category_id"}
    <ul class="page-categories">
        <li class="page-categories__item {if !$smarty.request.category_id}active{/if}"><a href="{$c_url|fn_url}">{__('all')}</a></li>
        {foreach $blog_category_ids as $blog_category_id}
            <li class="page-categories__item {if $smarty.request.category_id == $blog_category_id}active{/if}"><a href="{"`$c_url`&category_id=`$blog_category_id`"}">{$blog_category_id|fn_blog_categories_get_category_name}</a></li>
        {/foreach}
    </ul>
{/if}