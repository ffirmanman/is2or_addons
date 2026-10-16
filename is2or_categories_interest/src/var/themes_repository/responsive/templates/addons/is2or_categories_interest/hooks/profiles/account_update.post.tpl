{if $runtime.mode == 'update'}
{include file="common/subheader.tpl" title="{__('is2or_categories_interest.categories_interest')}"}
{foreach from=$groups item="group"}
    <div style="margin-bottom: 5px;">
        <input type="checkbox" class="categories-interest-checkbox" value="{$group.group_id}" id="tags_group_interest_{$group.group_id}" name="user_data[tags_group_interest][]" {if $group.group_id|in_array:$tags_group_interest}checked{/if} />
        <label class="checkbox" for="tags_group_interest_{$group.group_id}" style="display: inline-block; text-align: center">
            {* <img src="{$group.main_pair.icon.image_path}" style="height: 100px; margin: 5px 10px; display: block"> *}
            <span style="text-align: center;">{$group.group_name}</span>
        </label>
    </div>
{/foreach}
{/if}

{* Tags *}
{* {if $addons.tags}
{script src="js/lib/select2/dist/js/select2.full.min.js"}

{include file="common/subheader.tpl" title="{__('is2or_categories_interest.tags_interest')}"}
<div id="tags_interest_block">
    <div class="ty-control-group" style="margin-bottom: 5px;">
        <select name="user_data[tags_interest][]" class="ty-profile-field__select" id="tags_interest" multiple>
            {foreach from=$tags item="tag"}
                <option value="{$tag.tag_id}" {if $tag.tag_id|in_array:$tags_interest}selected="selected"{/if}>{$tag.tag}</option>
            {/foreach}
        </select>
    </div>
<!--tags_interest_block--></div>

<script>
    $(function() {
        $('#tags_interest').select2()

        $(document).on('change', '.categories-interest-checkbox', function() {
            var category_ids = [];
            
            $('.categories-interest-checkbox:checkbox:checked').each(function() {
                category_ids.push($(this).val());
            });

            const currentValue = $('#tags_interest').val();
            
            $.ceAjax(
                'request',
                '{fn_url("profiles.tags_interest")}&category_ids=' + category_ids.join(','),
                {
                    callback: function(data) {
                        const tags = JSON.parse(data.text);
                        const options = Object.keys(tags).map(index => {
                            return {
                                text: tags[index].tag, 
                                id: index
                            };
                        });

                        const currentValue = $('#tags_interest').val();

                        $('#tags_interest').empty().select2({
                            data: options,
                        });

                        $('#tags_interest').val(currentValue).change();
                    }
                }
            );
        });
    });
</script>
{/if} *}