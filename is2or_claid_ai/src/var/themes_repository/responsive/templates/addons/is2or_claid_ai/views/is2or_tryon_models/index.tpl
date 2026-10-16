{capture name="mainbox_title"}{__("is2or_claid_ai.tryon_models")}{/capture}

<div class="ty-grid-list">
    <div class="ty-column6">
        <div class="is2or-models__box">
            <div class="is2or-models is2or-models__empty">
                <a href="{"is2or_tryon_models.add"|fn_url}" class="cm-dialog-opener cm-dialog-auto-size cm-dialog-destroy-on-close" data-ca-dialog-title="{__('is2or_claid_ai.add_model')}"><i class="ty-icon ty-icon-plus"></i></a>
            </div>
        </div>
    </div>
    {foreach $models as $model}
    <div class="ty-column6">
        <div class="is2or-models__box">
            <div class="is2or-models">
                {$image_url = $model.main_pair.detailed.image_path}
                <img src="{$image_url}" class="is2or-models__img">

                <div class="is2or-model__actions">
                    <div>
                        <a href="{$image_url}" target="_blank" class="cm-tooltip ty-btn" title="{__('preview')}">
                            <i class="ty-icon ty-icon-eye-open"></i>
                        </a>
                        <a href="{"is2or_tryon_models.delete?model_id=`$model.model_id`"|fn_url}" class="cm-tooltip ty-btn cm-confirm cm-post delete" title="{__('delete')}">
                            <i class="ty-icon ty-icon-trashcan"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {/foreach}
</div>