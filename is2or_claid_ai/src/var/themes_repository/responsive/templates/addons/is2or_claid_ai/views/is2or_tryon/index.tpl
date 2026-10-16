{if $tryon_results}
    <h3>{__('is2or_claid_ai.past_results')}</h3>
    <div class="is2or-tryon__models">
        {foreach $tryon_results as $result}
            <div class="is2or-tryon__model is2or-tryon__result">
                <img src="/images/{$result.path}" />
            </div>
        {/foreach}
    </div>
{/if}

<h3>{__('is2or_claid_ai.choose_model')}:</h3>
<div class="is2or-tryon__models">
    <input type="hidden" id="tryon_model_id" />
    <input type="hidden" id="tryon_product_id" value="{$smarty.request.product_id}" />

    <div class="is2or-tryon__model is2or-tryon__add-model">
        <a href="{"is2or_tryon_models.index"|fn_url}">
            <i class="ty-icon-plus"></i>
        </a>
    </div>

    {foreach $tryon_models as $model}
        <div class="is2or-tryon__model" data-model-id="{$model.model_id}">
            {include file="common/image.tpl" images=$model.main_pair}
        </div>
    {/foreach}
</div>

<div class="buttons-container">
    <button type="button" class="ty-btn cm-dialog-closer" id="is2or_tryon_close">{__('close')}</button>
    <div class="ty-float-right">
        <button type="button" class="ty-btn ty-btn__secondary" id="is2or_tryon_generate">{__('generate')}</button>
    </div>
</div>

<script>
(function(_, $) {
    $(document).on('click', '.is2or-tryon__model:not(.is2or-tryon__add-model)', function() {
        var model_id = $(this).data('model-id');

        if (model_id) {
            $('#tryon_model_id').val(model_id);
    
            $('.is2or-tryon__model').removeClass('is2or-tryon__model--active');
            $(this).addClass('is2or-tryon__model--active');
        }
    });

    $(document).on('click', '.is2or-tryon__result', function() {
        const img = $(this).find('img');

        if (img) {
            const src = img.attr('src');
            previewImage(src);
        }
    })

    $(document).on('click', '#is2or_tryon_generate', function() {
        const $modelId = $('#tryon_model_id').val();
        const $productId = $('#tryon_product_id').val();

        if (!$modelId) {
            return $.ceNotification('show', {
                type: 'W',
                title: '{__("warning")}',
                message: '{__("is2or_claid_ai.please_choose_model")}',
                message_state: 'I'
            });
        }

        $.ceAjax('request', fn_url('is2or_tryon.generate'), {
            method: 'post',
            data: {
                model_id: $modelId,
                product_id: $productId
            },
            callback: function(response) {
                result = response.result;
                if (typeof result.path != 'undefined') {
                    // const link = document.createElement('a');
                    // link.href = '/images/' + result.path;
                    // link.target = "_blank";
                    // link.rel = "noopener noreferrer";
                    // document.body.appendChild(link);
                    // link.click();
                    // link.remove();

                    previewImage('/images/' + result.path);
                }
            }
        });
    });

    function previewImage(src) {
        $.ceDialog('get_last').ceDialog('close');
        $('#is2or_tryon_result_image').attr('src', src);
        $('#is2or_tryon_result_dialog_opener').trigger('click');
    }


})(Tygh, Tygh.$);
</script>