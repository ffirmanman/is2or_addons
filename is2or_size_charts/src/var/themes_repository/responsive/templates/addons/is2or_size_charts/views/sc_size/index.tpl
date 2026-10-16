<form method="post" action="{"sc_size.update"|fn_url}" class="ty-account">
    {foreach $customer_sizes as $size_key => $size_name}
    <div class="ty-control-group">
        <label class="ty-control-group__title" for="elm_{$size_key}">{$size_name} (cm):</label>
        <input id="elm_{$size_key}" type="text" name="data[{$size_key}]" value="{$user_data.is2or_sizes[{$size_key}]}" class="ty-input-text" />
    </div>
    {/foreach}

    <div class="buttons-container">
        <button type="submit" class="ty-btn ty-btn__primary">{__("save")}</button>
    </div>
</form>