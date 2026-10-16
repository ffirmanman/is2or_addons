{component name="configurable_page.field" entity="products" tab="detailed" section="information" field="allow_notes"}
    <div class="control-group">
        <label class="control-label" for="elm_allow_notes">{__("is2or_product_notes.allow_notes")}:</label>
        <div class="controls">
            <input type="hidden" name="product_data[allow_notes]" value="N" />
            <input type="checkbox" id="elm_allow_notes" name="product_data[allow_notes]" value="Y" {if $product_data.allow_notes == "YesNo::YES"|enum}checked{/if} >
        </div>
    </div>
{/component}