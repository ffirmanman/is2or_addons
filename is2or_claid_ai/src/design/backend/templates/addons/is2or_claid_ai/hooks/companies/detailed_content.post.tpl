{if !$runtime.company_id}
{include file="common/subheader.tpl" title=__("is2or_claid_ai.image_enhancer") target="#is2or_image_enhancer"}

<div id="is2or_image_enhancer" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label">{__("is2or_claid_ai.credits")}:</label>
            <div class="controls">
                <input type="text" name="company_data[is2or_claid_credits]" value="{$company_data.is2or_claid_credits|default:0}" />
            </div>
        </div>
    </fieldset>
</div>
{/if}