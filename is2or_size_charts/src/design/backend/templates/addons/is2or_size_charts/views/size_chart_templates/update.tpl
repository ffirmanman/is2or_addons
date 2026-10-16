{if $data}
    {assign var="id" value=$data.template_id}
{else}
    {assign var="id" value=0}
{/if}

{assign var="allow_save" value=true}
{$show_save_btn = $allow_save scope = root}

{capture name="mainbox"}

    <form action="{""|fn_url}" method="post" name="data_update_form"
          class="form-horizontal form-edit {if !$allow_save} cm-hide-inputs{/if}">
        <input type="hidden" name="id" value="{$data.template_id}"/>
        {capture name="tabsbox"}
            <div class="hidden" id="content_general">
                <fieldset>
                    <div class="control-group">
                        <label class="cm-required control-label"
                               for="elm_title">{__("is2or_size_charts.title")}:</label>
                        <div class="controls">
                            <input type="text" id="elm_title" class="input-large" name="data[title]"
                                   size="8" value="{$data.title}"/>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label">{__("is2or_size_charts.columns")}:</label>
                        <div class="controls">
                            {include file="addons/is2or_size_charts/views/size_chart_templates/components/columns_table.tpl"}
                        </div>
                    </div>
                </fieldset>
            </div>
        {/capture}
        {include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox group_name=$runtime.controller active_tab=$smarty.request.selected_section track=true}

        {capture name="buttons"}
            {if !$id}
                {include file="buttons/save_cancel.tpl" but_name="dispatch[size_chart_templates.update]" but_role="submit-link" but_target_form="data_update_form"}
            {else}
                {if !$show_save_btn}
                    {assign var="hide_first_button" value=true}
                    {assign var="hide_second_button" value=true}
                {/if}
                {include file="buttons/save_cancel.tpl" but_name="dispatch[size_chart_templates.update]" hide_first_button=$hide_first_button hide_second_button=$hide_second_button but_role="submit-link" but_target_form="data_update_form" save=$id}
            {/if}
        {/capture}
    </form>

{/capture}

{if $id}
    {capture name="page_title"}
        {__("is2or_size_charts.editing_template")}: {$data.title}
    {/capture}
{else}
    {capture name="page_title"}
        {__("is2or_size_charts.new_template")}
    {/capture}
{/if}

{include file="common/mainbox.tpl"
title=$smarty.capture.page_title
content=$smarty.capture.mainbox
buttons=$smarty.capture.buttons
}