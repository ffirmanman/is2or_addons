{if $data}
    {assign var="id" value=$data.chart_id}
{else}
    {assign var="id" value=0}
{/if}

{* assign var="allow_save" value=true *}
{$show_save_btn = $allow_save scope = root}

{capture name="mainbox"}

    <form action="{""|fn_url}" method="post" name="data_update_form"
          class="form-horizontal form-edit {if !$allow_save} cm-hide-inputs{/if}">
        <input type="hidden" name="id" value="{$data.chart_id}"/>
        <input type="hidden" class="cm-no-hide-input" name="selected_section" id="selected_section" value="{$selected_section}" />
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
                        <label class="cm-required control-label"
                               for="elm_comment">{__("is2or_size_charts.comment")}:</label>
                        <div class="controls">
                            <input type="text" id="elm_comment" class="input-large" name="data[comment]"
                                   size="8" value="{$data.comment}"/>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="cm-required control-label"
                               for="elm_comment">{__("is2or_size_charts.template")}:</label>
                        <div class="controls">
                            {if $id}
                                <span class="shift-input">{$data.template_name}</span>
                            {else}
                                <select name="data[template_id]" id="elm_template_id">
                                    {foreach from=$templates item="tpl" key="tpl_id"}
                                        <option value="{$tpl_id}">{$tpl}</option>
                                    {/foreach}
                                </select>
                            {/if}
                        </div>
                    </div>

                    {if $runtime.company_id!=0 && $data.status=="P"}
                    <div class="control-group">
                        <label class="control-label"
                               for="elm_comment">{__("status")}:</label>
                        <div class="controls">
                            <span class="shift-input">{__("pending")}</span>
                            <input type="hidden" name="data['status']" value="P" />
                        </div>
                    </div>
                    {else}
                        {include file="common/select_status.tpl"
                        input_name="data[status]"
                        id="elm_status"
                        obj=$data}
                    {/if}

                    <div class="control-group">
                        <label class="control-label"
                               for="elm_comment">{__("is2or_size_charts.text_before")}:</label>
                        <div class="controls">
                            <textarea id="elm_text_before"
                                      name="data[text_before]"
                                      cols="55"
                                      rows="8"
                                      class="cm-wysiwyg input-large"
                                      data-ca-is-block-manager-enabled="{fn_check_view_permissions("block_manager.block_selection", "GET")|intval}"
                            >{$data.text_before}</textarea>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label"
                               for="elm_text_after">{__("is2or_size_charts.text_after")}:</label>
                        <div class="controls">
                            <textarea id="elm_text_after"
                                      name="data[text_after]"
                                      cols="55"
                                      rows="8"
                                      class="cm-wysiwyg input-large"
                                      data-ca-is-block-manager-enabled="{fn_check_view_permissions("block_manager.block_selection", "GET")|intval}"
                            >{$data.text_after}</textarea>
                        </div>
                    </div>

                </fieldset>

            </div>
            {if $id}
            <div class="hidden" id="content_variants">
                <fieldset>
                    <legend>{__('is2or_size_charts.customer_size_recomendation')}</legend>
                    {foreach $data.template.columns as $column}
                        <div class="control-group">
                            <label class="control-label">{$column.title}:</label>
                            <div class="controls">
                                <select name="data[customer_sizes][{$column.code}]">
                                    <option value="">--</option>
                                    {foreach $customer_sizes as $size_code => $size_text}
                                        <option {if $data.customer_sizes[$column.code] == $size_code}selected="selected"{/if} value="{$size_code}">{$size_text}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                    {/foreach}
                </fieldset>

                <fieldset>
                    <legend>{__("is2or_size_charts.variants")}</legend>
                {* <div class="control-group"> *}
                    {* <label class="control-label">{__("is2or_size_charts.variants")}:</label> *}
                    {* <div class="controls"> *}
                    <div>
                        {include file="addons/is2or_size_charts/views/size_charts/components/variants_table.tpl" template=$data.template variants=$data.variants}
                    </div>
                    {* </div> *}
                {* </div> *}
                </fieldset>
            </div>
            {/if}
            <div class="hidden" id="content_applies">
                {include file="addons/is2or_size_charts/views/size_charts/components/chart_applies.tpl"}
            </div>
            <div class="hidden" id="content_sample">
                {include file="addons/is2or_size_charts/views/size_charts/components/sample_chart.tpl" chart=$data.chart}
            </div>
        {/capture}
        {include file="common/tabsbox.tpl"
            content=$smarty.capture.tabsbox
            group_name=$runtime.controller
            active_tab=$selected_section
            track=true
            show_tabs_navigation=false
        }

        {capture name="buttons"}
            {if !$id}
                {include file="buttons/save_cancel.tpl" but_name="dispatch[size_charts.update]" but_role="submit-link" but_target_form="data_update_form"}
            {else}
                {if !$show_save_btn}
                    {assign var="hide_first_button" value=true}
                    {assign var="hide_second_button" value=true}
                {/if}
                {include file="buttons/save_cancel.tpl" but_name="dispatch[size_charts.update]" hide_first_button=$hide_first_button hide_second_button=$hide_second_button but_role="submit-link" but_target_form="data_update_form" save=$id}
            {/if}
        {/capture}
    </form>

{/capture}

{if $id}
    {capture name="page_title"}
        {__("is2or_size_charts.editing_size_chart")}: {$data.title}
    {/capture}
{else}
    {capture name="page_title"}
        {__("is2or_size_charts.new_size_chart")}
    {/capture}
{/if}

{include file="common/mainbox.tpl"
title=$smarty.capture.page_title
content=$smarty.capture.mainbox
buttons=$smarty.capture.buttons
}