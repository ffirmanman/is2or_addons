{include file="common/subheader.tpl" title=__("is2or_claid_ai.tryon") target="#is2or_tryon"}

<div id="is2or_tryon" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label">{__("is2or_claid_ai.enable_tryon")}:</label>
            <div class="controls">
                <input type="hidden" name="category_data[is2or_tryon_enabled]" value="N" />
                <input type="checkbox" name="category_data[is2or_tryon_enabled]" value="Y" {if $category_data.is2or_tryon_enabled == 'Y'}checked="checked"{/if} />
            </div>
        </div>
    </fieldset>
</div>