<div class="control-group">
    <label class="control-label">{__("show_only_on_product_bundle")}:</label>
    <div class="controls">
        <input type="hidden" name="product_data[show_only_on_product_bundle]" value="N" />
        <input type="checkbox" id="show_only_on_product_bundle" name="product_data[show_only_on_product_bundle]" value="Y" {if $product_data.show_only_on_product_bundle == 'Y'}checked="checked"{/if} />
    </div>
</div>