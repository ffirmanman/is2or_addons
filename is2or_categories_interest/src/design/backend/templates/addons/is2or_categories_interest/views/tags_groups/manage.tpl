{capture name="mainbox"}
    {include file="common/pagination.tpl" save_current_page=true save_current_url=true pagination=$search}
    {if $groups}
        <div class="table-responsive-wrapper">
            <table class="table table--relative table-responsive">
                <thead>
                    <tr>
                        <th>{include file="common/table_col_head.tpl" type="group_id" text=__('is2or_categories_interest.group_id')}</th>
                        <th>{include file="common/table_col_head.tpl" type="group_name" text=__('is2or_categories_interest.group_name')}</th>
                        <th>{include file="common/table_col_head.tpl" text=__('image')}</th>
                        <th>{include file="common/table_col_head.tpl" text=__('tags')}</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $groups as $group}
                        <tr>
                            <td>{$group.group_id}</td>
                            <td>{$group.group_name}</td>
                            <td>
                                {include
                                    file="common/image.tpl"
                                    image=$group.main_pair.icon
                                    image_id=$group.main_pair.image_id
                                    image_width=100
                                    image_height=100
                                    href="tags_groups.update?group_id=`$group.group_id`"|fn_url
                                }
                            </td>
                            <td>{$group.tags}</td>
                            <td width="9%" class="nowrap right">
                                <div class="hidden-tools">
                                    {capture name="tools_list"}
                                        <li>{btn type="list" text=__("edit") href="tags_groups.update?group_id=`$group.group_id`"}</li>
                                        {if !$hide_inputs_if_shared_product}
                                            <li>{btn
                                                    type="list"
                                                    text=__("delete")
                                                    class="cm-confirm text-error"
                                                    href="tags_groups.delete?group_id=`$group.group_id`"
                                                    method="POST"
                                                }
                                            </li>
                                        {/if}
                                    {/capture}
                                    {dropdown content=$smarty.capture.tools_list}
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    {else}
        <p class="no-items">{__("no_data")}</p>
    {/if}
    {include file="common/pagination.tpl"}
{/capture}

{capture name="page_title"}
    {__("is2or_categories_interest.tags_groups")}
{/capture}

{capture name="adv_buttons"}
    {include file="common/tools.tpl" tool_href="tags_groups.add" prefix="top" hide_tools=true title=__("is2or_categories_interest.add_group") icon="icon-plus"}
{/capture}

{include file="common/mainbox.tpl"
    content=$smarty.capture.mainbox
    title=$smarty.capture.page_title
    adv_buttons=$smarty.capture.adv_buttons
}
