{if $product.tryon_enabled && $product.garments}
    <div class="hidden" id="is2or_tryon_result_dialog">
        <div style="padding-bottom: 10px;">
            <img id="is2or_tryon_result_image" style="max-height: 70vh; max-width: 100%;" />

            <div class="buttons-container">
                <div class="ty-float-right">
                    <button type="button" class="ty-btn cm-dialog-closer" style="margin-bottom: 0;">{__('close')}</button>
                </div>
            </div>
        </div>
    </div>
    <button style="display: none;" class="cm-dialog-opener cm-dialog-auto-size" data-ca-dialog-title="{__('is2or_claid_ai.result')}" data-ca-target-id="is2or_tryon_result_dialog" id="is2or_tryon_result_dialog_opener">&nbsp;</button>

    {if $auth.user_id}
        <a href="{"is2or_tryon.index?product_id=`$product.product_id`"|fn_url}" class="ty-btn ty-btn__tertiary cm-dialog-opener cm-dialog-destroy-on-close" data-ca-dialog-title="{__('is2or_claid_ai.tryon')}" id="is2or_tryon">{__('is2or_claid_ai.tryon')}</a>
    {else}
        <a href="{"auth.login_form"|fn_url}" class="ty-btn ty-btn__tertiary">{__('is2or_claid_ai.tryon')}</a>
    {/if}
{/if}
