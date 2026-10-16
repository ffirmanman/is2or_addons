{if $garment}
    {assign var="id" value=$garment.garment_id}
{else}
    {assign var="id" value=0}
{/if}
<form action="{""|fn_url}" method="post" id="tryon_garment_form" name="tryon_garment_form" class="form-edit form-horizontal" enctype="multipart/form-data">
    <input type="hidden" name="garment_id" value="{$id}" />
    <input type="hidden" name="garment_data[product_id]" value="{$smarty.request.product_id}" />

    <div class="control-group">
        <label class="control-label" for="elm_pos">{__("position")}:</label>
        <div class="controls">
            <input type="text" name="garment_data[position]" id="elm_pos" size="10" value="{$garment.position}" class="input-small" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label">{__('image')}:</label>
        <div class="controls">
            {include file="common/attach_images.tpl"
                image_name="is2or_tryon_garment"
                image_object_type="is2or_tryon_garment"
                image_pair=$garment.main_pair
                no_thumbnail=true
                hide_titles=true
            }
        </div>
    </div>

    <div class="buttons-container">
        {include file="buttons/save_cancel.tpl" but_name="dispatch[is2or_product_garments.update]" save=$id cancel_action="close"}
    </div>
</form>