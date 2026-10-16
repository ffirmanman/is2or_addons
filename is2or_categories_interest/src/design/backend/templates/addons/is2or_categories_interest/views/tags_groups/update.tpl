{if $group}
    {assign var="id" value=$group.group_id}
{else}
    {assign var="id" value="0"}
{/if}

{capture name="mainbox"}
    {capture name="tabsbox"}
        <form action="{""|fn_url}" enctype="multipart/form-data" method="post" name="tags_groups_form" class="form-horizontal form-edit">
            <input type="hidden" name="group_id" value="{$id}" />

            <div class="content_general">
                <fieldset>
                    <div class="control-group">
                        <label class="control-label cm-required" for="tag_group_name">{__('is2or_categories_interest.group_name')}</label>
                        <div class="controls">
                            <input type="text" name="group_data[group_name]" id="tag_group_name" value="{$group.group_name}" class="input-large" />
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label">{__('image')}</label>
                        <div class="controls">
                            {include file="common/attach_images.tpl"
                                image_name="tags_group"
                                image_object_type="tags_group"
                                image_pair=$group.main_pair
                                image_object_id=$id
                                no_detailed=true
                                hide_titles=true
                            }
                        </div>
                    </div>
                    <div class="control-group" id="group_tags_wrapper">
                        <label class="control-label cm-required" for="group_tags">{__('tags')}</label>
                        <div class="controls">
                            <select name="group_data[tags_ids][]" multiple class="input-large select2" id="group_tags">
                                {foreach $tags as $tag}
                                    <option value="{$tag.tag_id}" {if $tag.tag_id|in_array:$group.tags_ids}selected="selected"{/if}>{$tag.tag}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </fieldset>
            </div>
        </form>
    {/capture}

    {include file="common/tabsbox.tpl"
        content=$smarty.capture.tabsbox
        track=true
        active_tab=$smarty.request.selected_section|default:"general"
    }
{/capture}

{capture name="page_title"}
    {if $group}{$group.group_name}{else}{__('is2or_categories_interest.add_group')}{/if}
{/capture}

{capture name="buttons"}
    {include file="buttons/save_cancel.tpl" but_name="dispatch[tags_groups.update]" but_role="submit-link" but_target_form="tags_groups_form" save=$id}
{/capture}

{include file="common/mainbox.tpl"
    content=$smarty.capture.mainbox
    title=$smarty.capture.page_title
    buttons=$smarty.capture.buttons
}

<style>
.help-inline {
    display: block !important;
}
</style>

{script src="js/lib/select2/dist/js/select2.full.min.js"}
<script>
    $('.select2').select2();
</script>
