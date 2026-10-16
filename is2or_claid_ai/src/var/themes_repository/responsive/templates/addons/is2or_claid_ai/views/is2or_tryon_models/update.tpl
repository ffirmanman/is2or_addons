<form method="post" action="{""|fn_url}" enctype="multipart/form-data" style="min-width: 360px;">
    <input type="hidden" name="model_data[user_id]" value="{$auth.user_id}" />

    <div class="ty-control-group">
        <label class="ty-control-group__title cm-required" for="is2or_model_file">{__('image')}:</label>

        <input type="hidden" name="is2or_tryon_model_image_data[0][pair_id]" value="" />
        <input type="hidden" name="is2or_tryon_model_image_data[0][type]" value="M" />
        <input type="hidden" name="is2or_tryon_model_image_data[0][object_id]" value="0" />
        <input type="hidden" name="is2or_tryon_model_image_data[0][image_alt]" value="" />
        <input type="hidden" name="is2or_tryon_model_image_data[0][detailed_alt]" value="" />
        
        <input type="file" name="file_is2or_tryon_model_image_icon[0]" class="hidden" />
        <input type="hidden" name="file_is2or_tryon_model_image_icon[0]" value="is2or_tryon_model" />
        <input type="hidden" name="type_is2or_tryon_model_image_icon[0]" value="local" />
        <input type="hidden" name="is_high_res_is2or_tryon_model_image_icon[0]" value="N" />

        <input type="file" name="file_is2or_tryon_model_image_detailed[0]" id="is2or_model_file" />
        <input type="hidden" name="file_is2or_tryon_model_image_detailed[0]" value="is2or_tryon_model" id="is2or_model_file_value" />
        <input type="hidden" name="type_is2or_tryon_model_image_detailed[0]" value="local" />
        <input type="hidden" name="is_high_res_is2or_tryon_model_image_detailed[0]" value="N" />
    </div>

    <div class="buttons-container">
        {include file="buttons/button.tpl" but_text=__("upload") but_meta="ty-btn__secondary" but_role="submit" but_name="dispatch[is2or_tryon_models.update]"}
    </div>
</form>

<script>
(function(_,$){
    $(_.doc).on('change', '#is2or_model_file', function() {
        if (this.files && this.files[0]) {
            console.log(this.files[0])
            $('#is2or_model_file_value').val($(this).val());
        }
    })
})(Tygh, Tygh.$);
</script>