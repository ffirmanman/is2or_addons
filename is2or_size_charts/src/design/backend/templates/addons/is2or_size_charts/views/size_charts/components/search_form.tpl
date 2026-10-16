
<div class="sidebar-row">
    <h6>{__("search")}</h6>
    <form action="{""|fn_url}" name="charts_filter_form" method="get">
        <div class="sidebar-field">
            <label>{__("status")}:</label>
            <select name="status">
                <option {if "" == $search.status}selected="selected"{/if} value="">{__("any")}</option>
                <option {if "A" == $search.status}selected="selected"{/if} value="A">{__("active")}</option>
                <option {if "D" == $search.status}selected="selected"{/if} value="D">{__("disabled")}</option>
                <option {if "P" == $search.status}selected="selected"{/if} value="P">{__("pending")}</option>
            </select>
        </div>
        <div class="sidebar-field">
            <label>{__("is2or_size_charts.template")}:</label>
            <select name="template_id">
                <option {if "" == $search.template_id}selected="selected"{/if} value="">{__("any")}</option>
                {foreach from=$templates item="tpl" key="tpl_id"}
                    <option {if $search.template_id==$tpl_id}selected="selected"{/if} value="{$tpl_id}">{$tpl}</option>
                {/foreach}
            </select>
        </div>
        {if $runtime.company_id==0}
        <div class="sidebar-field ajax-select">
            <div class="control-group">
                <label class="control-label">{__("vendor")}</label>
                <div class="controls">
                    {if !$runtime.company_id}
                        <input type="hidden" name="company_id" id="search_hidden_vendor" value="{$search.vendor|default:'all'}" />
                        {include file="common/ajax_select_object.tpl"
                        data_url="companies.get_companies_list?show_all=Y"
                        text=$search.company_id|fn_get_company_name|default:__("all_vendors")
                        result_elm="search_hidden_vendor"
                        id="company_search"
                        relative_dropdown=false
                        }
                    {else}
                        {$search.company_id|fn_get_company_name}
                    {/if}
                </div>
            </div>
        </div>
        {/if}
        {include file="buttons/search.tpl" but_name="dispatch[size_charts.manage]" method="GET"}
    </form>
</div>
