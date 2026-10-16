{if $product_data.product_id}
{* IMAGE ENHANCER *}
<div id="content_is2or_claid_ai" {if $selected_section != 'is2or_claid_ai'}class="hidden"{/if}>
    <div class="hidden" id="is2or_claid_tnc">
        {$addons.is2or_claid_ai.tnc|nl2br nofilter}

        <div class="buttons-container">
            <button type="button" class="btn btn-primary cm-dialog-closer">{__('close')}</button>
        </div>
    </div>

    <div class="is2or-ie__container">
        <h4>{__('is2or_claid_ai.image_enhancer_title')}</h4>
        <p>{__('is2or_claid_ai.image_enhancer_subtitle')} <a href="#" class="cm-dialog-opener" data-ca-target-id="is2or_claid_tnc" data-ca-dialog-title="{__('is2or_claid_ai.terms_and_conditions')}">{__('is2or_claid_ai.read_terms_and_conditions')}</a></p>
        <br>

        {if $runtime.company_id}
        <p>
            <span id="is2or_credit_value">{__('is2or_claid_ai.remaining_credits', ['[credit]' => $is2or_claid_credits])}</span>
            {* TODO *}
            <a class="btn btn-primary buy-credit cm-dialog-opener cm-dialog-auto-size cm-dialog-destroy-on-close" href="{"is2or_claid_ai.buy_credit"|fn_url}" data-ca-dialog-title="{__('is2or_claid_ai.buy_credit')}">{__('is2or_claid_ai.buy_more_credit')}</a>
        </p>
        <br>
        {/if}

        <div class="row-fluid">
            <div class="span5">
                <p>{__('is2or_claid_ai.source_image')}:</p>

                <div class="is2or-ie__section">
                    <div class="is2or-ie__img">
                        <div class="is2or-ie__img-wrapper">
                            <img src="{$images_dir}/addons/is2or_claid_ai/placeholder.jpg" id="is2or_ie_source_preview">
                            <button type="button" id="is2or_ie_upload_image" class="btn btn-primary">{__('is2or_claid_ai.upload_image')}</button>

                            <div class="is2or-ie__image-actions hidden" id="is2or_ie_source_actions">
                                <div>
                                    <a href="javascript:void(0)" class="cm-tooltip is2or-ie__img-preview" title="{__("preview")}">
                                        {include_ext file="common/icon.tpl" class="icon icon-eye-open"}
                                    </a>
                                    <a href="javascript:void(0)" id="is2or_ie_source_remove" class="cm-tooltip" title="{__("remove")}">
                                        {include_ext file="common/icon.tpl" class="icon icon-trash"}
                                    </a>
                                </div>
                            </div>

                            <input type="hidden" id="is2or_ie_upload_path" />
                            <input type="file" accept="image/*" id="is2or_ie_upload_image_input" style="display: none;">
                        </div>
                    </div>

                    <div class="is2or-ie__buttons">
                        <div class="is2or-ie__button">
                            <button type="button" id="is2or_ie_remove_background" class="btn ">{__('is2or_claid_ai.remove_background')}</button>
                            <i class="icon-question-sign cm-tooltip" title="{__('is2or_claid_ai.remove_background_tooltip')}"></i>
                        </div>
                        <div class="is2or-ie__button">
                            <button type="button" id="is2or_ie_enhance_image" class="btn">{__('is2or_claid_ai.enhance_image')}</button>
                            <i class="icon-question-sign cm-tooltip" title="{__('is2or_claid_ai.enhance_image_tooltip')}"></i>
                        </div>
                        <div class="is2or-ie__button">
                            <button type="button" id="is2or_ie_generate_background" class="btn">{__('is2or_claid_ai.generate_background')}</button>
                            <i class="icon-question-sign cm-tooltip" title="{__('is2or_claid_ai.generate_background_tooltip')}"></i>
                        </div>
                        <div class="is2or-ie__button" id="is2or_ie_prompt__wrapper" style="display: none;">
                            <small>Enter a prompt to generate background:</small>
                            <textarea rows="3" id="is2or_ie_prompt"></textarea>

                            <button type="button" id="is2or_ie_generate_background_generate" class="btn btn-primary" style="margin-top: 10px;">{__('is2or_claid_ai.generate')}</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="span6" id="is2or_ie_result__wrapper" style="display: none;">
                <p>{__('is2or_claid_ai.result')}:</p>

                <div class="is2or-ie__section">
                    <div class="is2or-ie__img">
                        <div class="is2or-ie__img-wrapper">
                            <img src="{$images_dir}/addons/is2or_claid_ai/placeholder.jpg" id="is2or_ie_result_preview">

                            <div class="is2or-ie__image-actions">
                                <div>
                                    <a href="javascript:void(0)" class="cm-tooltip is2or-ie__img-preview" title="{__("preview")}">
                                        {include_ext file="common/icon.tpl" class="icon icon-eye-open"}
                                    </a>
                                </div>
                            </div>

                            <input type="hidden" id="is2or_ie_result_path" />
                        </div>
                    </div>

                    <div class="is2or-ie__buttons">
                        <div class="is2or-ie__button">
                            <button type="button" id="is2or_ie_add_result" class="btn btn-primary">{__('is2or_claid_ai.add_as_product_image')}</button>
                        </div>
                        <div class="is2or-ie__button">
                            <button type="button" id="is2or_ie_use_result" class="btn">{__('is2or_claid_ai.reuse_image')}</button>
                            <i class="icon-question-sign cm-tooltip" title="{__('is2or_claid_ai.reuse_image_tooltip')}"></i>
                        </div>
                        <div class="is2or-ie__button">
                            <button type="button" id="is2or_ie_download_result" class="btn">{__('download')}</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="span1" id="is2or_ie_past_results" {if !$is2or_claid_images}style="display: none;"{/if}>
                <p>{__('is2or_claid_ai.past_results')}:</p>
                <div class="is2or-ie__images">
                    {foreach from=$is2or_claid_images item=image}
                    <div class="is2or-ie__img-wrapper">
                        <img src="{$image.url}" data-is2or-path="{$image.path}">

                        <div class="is2or-ie__image-actions is2or-ie__past-result">
                            <div>
                                <a href="javascript:void(0)" class="cm-tooltip is2or-ie__img-preview" title="{__("preview")}">
                                    {include_ext file="common/icon.tpl" class="icon icon-eye-open"}
                                </a>
                            </div>
                        </div>
                    </div>
                    {/foreach}
                </div>
            </div>
        </div>
    </div>
</div>

{* TRY-ON *}
<div id="content_is2or_claid_tryon" {if $selected_section != 'is2or_claid_tryon'}class="hidden"{/if}>
    <div class="control-toolbar">
        <div class="control-toolbar__btns">
            <div class="control-toolbar__btns-right">
                {btn type="text"
                    id="add_garment_`$id`"
                    href="{"is2or_product_garments.add?product_id=`$product_data.product_id`"|fn_url}"
                    text=__("is2or_claid_ai.add_garment")
                    icon_first=true
                    icon="icon-plus"
                    class="btn cm-dialog-opener cm-dialog-destroy-on-close"
                    data=["data-ca-dialog-title" => __("is2or_claid_ai.add_garment")]
                }
            </div>
        </div>
    </div>

    {if $is2or_claid_garments}
        <table class="table">
            <thead>
                <tr>
                    <th width="7%">{__('position_short')}</th>
                    <th>{__('image')}</th>
                    <th width="10%">&nbsp;</th>
                    <th width="15%" class="right">{__('status')}</th>
                </tr>
            </thead>
            <tbody>
                {foreach $is2or_claid_garments as $garment}
                    <tr>
                        <td data-th="{__('position_short')}">{$garment.position}</td>
                        <td data-th="{__('image')}">
                            {if $garment.main_pair}
                                {$image_url = $garment.main_pair.detailed.image_path}
                                <a href="{$image_url}" target="_blank">
                                    <img src="{$image_url}" style="max-width: 150px; max-height: 150px;" />
                                </a>
                            {else}
                                <img src="{$images_dir}/addons/is2or_claid_ai/placeholder.jpg" style="max-width: 150px; max-height: 150px;">
                            {/if}
                        </td>
                        <td>
                            <div class="hidden-tools">
                                {capture name="tools_list"}
                                    <li>{btn
                                            type="list"
                                            text=__("edit")
                                            class="cm-dialog-opener cm-dialog-destroy-on-close"
                                            href="is2or_product_garments.update?product_id=`$product_data.product_id`&garment_id=`$garment.garment_id`"
                                            data=[
                                                "data-ca-dialog-title" => __('is2or_claid_ai.edit_garment')
                                            ]
                                        }
                                    </li>
                                    <li>{btn
                                            type="list"
                                            text=__("delete")
                                            class="text-error cm-confirm"
                                            href="is2or_product_garments.delete?product_id=`$product_data.product_id`&garment_id=`$garment.garment_id`"
                                            method="POST"
                                        }
                                    </li>
                                {/capture}
                                {dropdown content=$smarty.capture.tools_list}
                            </div>
                        </td>
                        <td data-th="{__('status')}" class="right">
                            {include file="common/select_popup.tpl"
                                type="is2or_product_garments"
                                id=$garment.garment_id
                                status=$garment.status
                                hidden=false
                                object_id_name="garment_id"
                                table="is2or_product_garments"
                            }
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    {else}
        <p class="no-items">{__('no_data')}</p>
    {/if}
</div>

<script>
(function(_, $) {
    $.extend(_, {
        is2or_claid_ai: {
            product_id: {$product_data.product_id},
            default_image: '{$images_dir}/addons/is2or_claid_ai/placeholder.jpg',
            company_credits: {$is2or_claid_credits|default:0},
            credit_costs: {
                remove_background: {$addons.is2or_claid_ai.remove_background_cost|default:0|intval},
                enhance_image: {$addons.is2or_claid_ai.enhance_image_cost|default:0|intval},
                generate_background: {$addons.is2or_claid_ai.generate_background_cost|default:0|intval}
            }
        }
    });

    _.tr({
        'is2or_claid_ai.source_image_empty': '{__("is2or_claid_ai.source_image_empty")}',
        'is2or_claid_ai.insufficient_credit': '{__("is2or_claid_ai.insufficient_credit")}',
        'is2or_claid_ai.remaining_credits': '{__("is2or_claid_ai.remaining_credits", ["[credit]" => "[credit]"])}',
        'is2or_claid_ai.prompt_empty': '{__("is2or_claid_ai.prompt_empty")}',
    });
})(Tygh, Tygh.$);

{script src="js/addons/is2or_claid_ai/func.js"}
</script>
{/if}