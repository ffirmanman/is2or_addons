{$company_id=$runtime.company_id}
{capture name="mainbox"}

    <form action="{""|fn_url}" method="post" name="insurers_form" class="{if $runtime.company_id} cm-hide-inputs{/if}">

        {include file="common/pagination.tpl" save_current_page=true save_current_url=true}
        {if $items}
            <table width="100%" class="table table-middle">
                <thead>
                <tr>
                    <th>{__("is2or_size_charts.title")}</th>
                    <th>{__("is2or_size_charts.comment")}</th>
                    <th>{__("is2or_size_charts.template")}</th>
                    <th width="5%">&nbsp;</th>
                    <th class="right" width="10%">{__("status")}</th>
                </tr>
                </thead>
                {foreach from=$items item="item"}
                    <tr>
                        <td>
                            <a href="{"size_charts.update?id=`$item.chart_id`"|fn_url}">{$item.title}</a>
                            {if $item.company_id==0}(Global){else} ({$item.company}){/if}
                        </td>
                        <td>
                            <a href="{"size_charts.update?id=`$item.chart_id`"|fn_url}">{$item.comment}</a>
                        </td>
                        <td>
                            <a href="{"size_charts.update?id=`$item.chart_id`"|fn_url}">{$item.template_name}</a>
                        </td>
                        <td class="nowrap">
                            {capture name="tools_list"}
                                {if $company_id==0 || $company_id==$item.company_id}
                                <li>{btn type="list" text=__("edit") href="size_charts.update?id=`$item.chart_id`"}</li>
                                <li>{btn type="list" class="text-error cm-confirm" text=__("delete") href="size_charts.delete?id=`$item.chart_id`" method="POST"}</li>
                                {/if}
                            {/capture}
                            <div class="hidden-tools">
                                {dropdown content=$smarty.capture.tools_list}
                            </div>
                        </td>
                        <td class="right">
                            {$status_editable=true}
                            {if $runtime.company_id!=0 && $item.status=='P'}{$status_editable=false}{/if}
                            {include file="common/select_popup.tpl" id=$item.chart_id status=$item.status hidden=""
                                object_id_name="chart_id" table="size_charts"
                                non_editable=!$status_editable
                            }
                        </td>
                    </tr>
                {/foreach}
            </table>
        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}

        {include file="common/pagination.tpl"}

    </form>
    {capture name="buttons"}
        {if $runtime.company_id==0}
        {capture name="tools_list"}
            <li><a href="{fn_url("size_chart_templates.manage")}">{__("is2or_size_charts.templates")}</a></li>
        {/capture}
        {dropdown content=$smarty.capture.tools_list}
        {/if}
    {/capture}

    {capture name="adv_buttons"}
        {include file="common/tools.tpl" tool_href="size_charts.add" prefix="top" title=__("add") hide_tools=true icon="icon-plus"}
    {/capture}

    {capture name="sidebar"}
        {include file="addons/is2or_size_charts/views/size_charts/components/search_form.tpl" dispatch="size_charts.manage"}
    {/capture}

{/capture}

{include file="common/mainbox.tpl" title=__("is2or_size_charts.size_charts")
content=$smarty.capture.mainbox
buttons=$smarty.capture.buttons
adv_buttons=$smarty.capture.adv_buttons
sidebar=$smarty.capture.sidebar
}