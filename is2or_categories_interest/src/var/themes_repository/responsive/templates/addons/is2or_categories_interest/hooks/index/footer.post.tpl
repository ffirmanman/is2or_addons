{if $smarty.session.show_interest_popup 
    && $auth.user_id
    && $_groups
    && !(
        $runtime.controller == 'profiles'
        && (
            $runtime.mode == 'add' || $runtime.mode == 'update'
        )
    )
}
    <a href="javascript:;" data-ca-target-id="as_interest_popup" id="as_interest_popup_button" class="hidden cm-dialog-opener cm-dialog-auto-size ty-btn ty-btn__secondary" rel="nofollow"></a>
    <div id="as_interest_popup" class="hidden" title="{__("is2or_categories_interest.categories_interest")}">
        <form name="update_interest_form" method="POST" action="{""|fn_url}">
            <input type="hidden" name="user_data[user_id]" value="{$auth.user_id}" />
            <input type="hidden" name="return_url" value="{$config.current_url|fn_url}" />
            <div class="as_interest_popup_inner">
                <div style="margin-bottom: 15px;">
                    <input type="checkbox" class="categories-interest-checkbox" id="categories_intereset_select_all" />
                    <label class="checkbox" for="categories_intereset_select_all">{__('select_all')}</label>
                </div>
                {foreach from=$_groups item="group"}
                    <div style="margin-bottom: 5px;">
                        <input type="checkbox" class="categories-interest-checkbox categories-interest-checkbox__item" value="{$group.group_id}" id="tags_group_interest_{$group.group_id}" name="user_data[tags_group_interest][]" {if $group.group_id|in_array:$_tags_group_interest}checked{/if} />
                        <label class="checkbox" for="tags_group_interest_{$group.group_id}">
                            <img src="{$group.main_pair.icon.image_path}" style="height: 100px; margin: 0 10px;">
                            {$group.group_name}
                        </label>
                    </div>
                {/foreach}

                {* {foreach from=$_categories item="category"}
                    <div style="margin-bottom: 5px;">
                        <input type="checkbox" class="categories-interest-checkbox" value="{$category.category_id}" id="categories_interest_{$category.category_id}" name="user_data[categories_interest][]" {if $category.category_id|in_array:$_categories_interest}checked{/if} />
                        <label class="checkbox" for="categories_interest_{$category.category_id}">{$category.category}</label>
                    </div>
                {/foreach}

                {if $addons.tags}
                    {include file="common/subheader.tpl" title="{__('is2or_categories_interest.tags_interest')}"}
                    <div class="ty-control-group" style="margin-bottom: 5px;">
                        <select name="user_data[tags_interest][]" class="ty-profile-field__select" id="tags_interest" multiple>
                            {foreach from=$_tags item="tag"}
                                <option value="{$tag.tag_id}" {if $tag.tag_id|in_array:$_tags_interest}selected="selected"{/if}>{$tag.tag}</option>
                            {/foreach}
                        </select>
                    </div>
                {/if} *}
            </div>
            <div class="as_interest_popup_footer">
                <button class="ty-btn ty-btn__secondary" type="submit" name="dispatch[profiles.update_categories_interest]">{__('save')}</button>
            </div>
        </form>
    </div>
    {script src="js/lib/select2/dist/js/select2.full.min.js"}
    <script>
        $(function() {
            $('#as_interest_popup_button').click();
            // $('#tags_interest').select2();

            $(document).on('click', '#categories_intereset_select_all', function() {
                if ($(this).prop('checked')) {
                    $('.categories-interest-checkbox__item').prop('checked', true);
                } else {
                    $('.categories-interest-checkbox__item').prop('checked', false);
                }
            });

            $.ceEvent('on', 'ce.dialogclose', function() {
                $.ceAjax(
                    'request',
                    '{fn_url("profiles.close_interest_popup")}',
                    {}
                );
            });

            // $(document).on('change', '.categories-interest-checkbox', function() {
            //     var category_ids = [];
                
            //     $('.categories-interest-checkbox:checkbox:checked').each(function() {
            //         category_ids.push($(this).val());
            //     });

            //     // 'dialogclosed'
                
            //     $.ceAjax(
            //         'request',
            //         '{fn_url("profiles.tags_interest")}&category_ids=' + category_ids.join(','),
            //         {
            //             callback: function(data) {
            //                 const tags = JSON.parse(data.text);
            //                 const options = Object.keys(tags).map(index => {
            //                     return {
            //                         text: tags[index].tag, 
            //                         id: index
            //                     };
            //                 });

            //                 const currentValue = $('#tags_interest').val();

            //                 $('#tags_interest').empty().select2({
            //                     data: options,
            //                 });

            //                 $('#tags_interest').val(currentValue).change();
            //             }
            //         }
            //     );
            // });
        });
    </script>
{/if}