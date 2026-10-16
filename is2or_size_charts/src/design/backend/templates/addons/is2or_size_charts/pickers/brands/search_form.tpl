{if $in_popup}
<div class="adv-search">
    <div class="group">
        {else}
        <div class="sidebar-row">
            <h6>{__("search")}</h6>
            {/if}
            <form name="user_search_form" action="{""|fn_url}" method="get" class="{$form_meta}">

                {if $smarty.request.redirect_url}
                    <input type="hidden" name="redirect_url" value="{$smarty.request.redirect_url}" />
                {/if}

                {if $selected_section != ""}
                    <input type="hidden" id="selected_section" name="selected_section" value="{$selected_section}" />
                {/if}

                {if $put_request_vars}
                    {array_to_fields data=$smarty.request skip=["callback"] escape=["data_id"]}
                {/if}

                {capture name="simple_search"}
                    {$extra nofilter}
                    <div class="sidebar-field">
                        <label for="elm_feature_id">{__("feature")}</label>
                        <div class="break">
                            <select name="feature_id" id="elm_feature_id">
                                <option value="" {if $search.feature_id==""}selected{/if}>{__("any")}</option>
                                {foreach from=$features key="f_id" item="f_title"}
                                <option value="{$f_id}" {if $search.feature_id==$f_id}selected{/if}>{$f_title}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="sidebar-field">
                        <label for="elm_feature_id">{__("brand")}</label>
                        <div class="break">
                            <input type="text" name="q" id="elm_q" value="{$search.q}" />
                        </div>
                    </div>
                {/capture}

                {capture name="advanced_search"}

                {/capture}

                {include
                    file="common/advanced_search.tpl"
                    simple_search=$smarty.capture.simple_search
                    advanced_search=$smarty.capture.advanced_search
                    dispatch=$dispatch
                    view_type="brands"
                    in_popup=$in_popup
                }

            </form>

            {if $in_popup}
        </div></div>
    {else}
</div><hr>
{/if}
