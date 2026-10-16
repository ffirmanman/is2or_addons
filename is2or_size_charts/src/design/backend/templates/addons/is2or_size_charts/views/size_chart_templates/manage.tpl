{capture name="mainbox"}

    <form action="{""|fn_url}" method="post" name="insurers_form" class="{if $runtime.company_id} cm-hide-inputs{/if}">

        {include file="common/pagination.tpl" save_current_page=true save_current_url=true}

        {if $items}
            <table width="100%" class="table table-middle">
                <thead>
                <tr>
                    <th>{__("is2or_size_charts.template")}</th>
                    <th>{__("is2or_size_charts.columns")}</th>
                    <th width="5%">&nbsp;</th>
                </tr>
                </thead>
                {foreach from=$items item="item"}
                    <tr>
                        <td>
                            <a href="{"size_chart_templates.update?id=`$item.template_id`"|fn_url}">{$item.title}</a>
                        </td>
                        <td>
                            <a href="{"size_chart_templates.update?id=`$item.template_id`"|fn_url}">{$item.columns_titles}</a>
                        </td>
                        <td class="nowrap">
                            {capture name="tools_list"}
                                <li>{btn type="list" text=__("edit") href="size_chart_templates.update?id=`$item.template_id`"}</li>
                                <li>{btn type="list" class="cm-confirm" text=__("delete") href="size_chart_templates.delete?id=`$item.template_id`" method="POST"}</li>
                            {/capture}
                            <div class="hidden-tools">
                                {dropdown content=$smarty.capture.tools_list}
                            </div>
                        </td>
                    </tr>
                {/foreach}
            </table>
        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}

        {include file="common/pagination.tpl"}

    </form>

    {capture name="adv_buttons"}
        {include file="common/tools.tpl" tool_href="size_chart_templates.add" prefix="top" title=__("add") hide_tools=true icon="icon-plus"}
    {/capture}

{/capture}

{include file="common/mainbox.tpl" title=__("is2or_size_charts.templates")
content=$smarty.capture.mainbox
buttons=$smarty.capture.buttons
adv_buttons=$smarty.capture.adv_buttons
}