{include file="addons/ab__video_gallery/components/helpers.tpl"}

{if $thumbnails_size}
    {assign var="th_size" value=$thumbnails_size|default:50}
{elseif $settings.abt__device == "desktop"}
    {assign var="th_size" value=$addons.ab__video_gallery.th_size|default:60}
{else}
    {assign var="th_size" value=50}
{/if}

{assign var="th_sum_size" value=$settings.Thumbnails.product_details_thumbnail_height|default:$pd_image_gallery_height / $th_size}

{$ab__vg_videos = $product.product_id|fn_ab__vg_get_videos}
{$ab__vg_settings = $product.product_id|fn_ab__vg_get_setting}
{$is_vertical = (($runtime.mode !== 'quick_view') && ($addons.ab__video_gallery.vertical === "YesNo::YES"|enum)) && $settings.abt__device !== "mobile"}
{$is_thumbnails_gallery = $settings.Appearance.thumbnails_gallery === "YesNo::YES"|enum}
{$total_count = ($product.image_pairs|count + $ab__vg_videos|count + 1)}
{$total_images = $product.image_pairs|count}
{$product_pos_enum = "Addons\Ab_videoGallery\VideoProductPositionTypes"}

{capture name="abt__ut2_vertical_gallery_width"}
    {if $total_count > $th_sum_size && !$is_thumbnails_gallery}{$th_size * 2 + 5}{elseif $total_count > 1}{$th_size}{/if}
{/capture}

{if $product.main_pair.icon || $product.main_pair.detailed}
    {assign var="image_pair_var" value=$product.main_pair}
{elseif $product.option_image_pairs}
    {assign var="image_pair_var" value=$product.option_image_pairs|reset}
{/if}

{if $image_pair_var.image_id}
    {assign var="image_id" value=$image_pair_var.image_id}
{else}
    {assign var="image_id" value=$image_pair_var.detailed_id}
{/if}

{if $image_pair_var || empty($ab__vg_videos)}
    {$total_images = $total_images + 1}
{/if}

{if !$preview_id}
    {assign var="preview_id" value=$product.product_id|uniqid}
{/if}

{$image_height_block = $image_height|default:$settings.Thumbnails.product_details_thumbnail_height}

{$images_wrapper_classes = "ab_vg-images-wrapper"}

{if !$nocarousel}
    {$images_wrapper_classes = "$images_wrapper_classes ab_vg-images-wrapper_mode_carousel"}
{/if}

{if $is_vertical && $total_count > 1}
    {$images_wrapper_classes = "$images_wrapper_classes ab_vg-images-wrapper_mode_inline"}
{/if}

{if $ab__vg_videos && $ab__vg_settings.replace_image === "YesNo::YES"|enum}
    {$images_wrapper_classes = "$images_wrapper_classes ab_vg-replace_image"}
{/if}

<div class="{$images_wrapper_classes}" data-ca-previewer="true" {if $is_vertical && $total_count > 1}style="--vg-thumb-width-size: {$smarty.capture.abt__ut2_vertical_gallery_width}px;"{/if}>
    {assign var="product_labels" value="product_labels_`$obj_prefix``$obj_id`"}
    {$smarty.capture.$product_labels nofilter}

    {$wrapper_styles = ""}
    {$wrapper_class = "ty-product-img"}

    {if !$nopreviewer}{$wrapper_class = "`$wrapper_class` cm-preview-wrapper"}{/if}
    {if !$nocarousel}
        {if $is_vertical && $total_count > 1}
            {if $settings.abt__ut2.products.multiple_product_images === 1}
                {$wrapper_styles = "`$wrapper_styles`height: `$image_height_block`px;"}
            {/if}
        {/if}

        {if $settings.abt__device !== "mobile"}
            {$wrapper_styles = "`$wrapper_styles`height: `$image_height_block`px;"}
        {/if}
    {/if}

    {if $is_vertical}
        {if $is_thumbnails_gallery}
            {$wrapper_class = "`$wrapper_class` ab-vg-vertical"}
        {else}
            {$wrapper_class = "`$wrapper_class` ab-vertical"}
        {/if}
    {/if}

    <div id="product_images_{$preview_id}" class="{$wrapper_class}"{if $wrapper_styles} style="{$wrapper_styles}"{/if}>
        {$videos = $ab__vg_videos}
        {$image_iterator = 0}

        {if $product.360_images}
            {$image_iterator = $image_iterator + 1}

            {$first_360_image = $product.360_images|reset}
            <div class="ty-product-img ty-center">
                <div
                    class="cloudimage-360"
                    id="360ImagesContainer{$product.product_id}"
                    data-folder="/images/360_images/{$product.product_id}/"
                    data-filename-x="{$first_360_image.prefix}-{literal}{index}{/literal}.{$first_360_image.extension}"
                    data-amount-x="{$product.360_images|count}"
                    data-drag-speed="120"
                    data-lazyload="true"
                ></div>
            </div>
        {/if}

        {call name=fn_ab__vg_get_videos_by_pos position="$product_pos_enum::TOP"|enum}
        {call name=fn_ab__vg_get_videos_by_pos position="$product_pos_enum::CUSTOM"|enum}

        {if $image_pair_var || empty($ab__vg_videos)}
            {$image_iterator = $image_iterator + 1}

            {include file="common/image.tpl" obj_id="`$preview_id`_`$image_id`" images=$image_pair_var
                     link_class="cm-image-previewer{if $nopreviewer} cm-previewer-only{/if}{if $ab__vg_videos && $ab__vg_settings.replace_image === "YesNo::YES"|enum} hidden{/if}"
                     image_width=$image_width image_height=$image_height image_id="preview[product_images_`$preview_id`]"}

            {call name=fn_ab__vg_get_videos_by_pos position="$product_pos_enum::CUSTOM"|enum}
        {/if}

        {foreach from=$product.image_pairs item="image_pair"}
            {$image_iterator = $image_iterator + 1}

            {if $image_pair.image_id}
                {assign var="img_id" value=$image_pair.image_id}
            {else}
                {assign var="img_id" value=$image_pair.detailed_id}
            {/if}

            {include file="common/image.tpl" images=$image_pair link_class="{if $nopreviewer}cm-previewer-only {/if}cm-image-previewer hidden"
                     obj_id="`$preview_id`_`$img_id`" image_width=$image_width image_height=$image_height
                     image_id="preview[product_images_`$preview_id`]" image_link_additional_attrs=["data-ca-image-order"=>$image_iterator-1]}

            {call name=fn_ab__vg_get_videos_by_pos position="$product_pos_enum::CUSTOM"|enum}
        {/foreach}

        {call name=fn_ab__vg_get_videos_by_pos position="$product_pos_enum::CUSTOM"|enum}
        {call name=fn_ab__vg_get_videos_by_pos position="$product_pos_enum::BOTTOM"|enum}
    </div>

    {* Video popups content. For tab with videos or for popup onclick *}
    {include file="addons/ab__video_gallery/components/video_popups.tpl"}

    {if !$nocarousel}
        {$custom_thumbnails = $settings.abt__ut2.products.view.thumbnails_gallery_format[$settings.abt__device] !== "default"}

        {if ($product.image_pairs || $ab__vg_videos) && !$custom_thumbnails}
            {$image_counter = -1}
            {$videos = $ab__vg_videos}
            {$image_iterator = 0}
            {$video_iterator = 0}
            {$image_classes="ty-product-thumbnails__item cm-thumbnails-mini"}

            {if $is_thumbnails_gallery}
                {$image_classes="`$image_classes` cm-gallery-item gallery"}

                <input type="hidden" name="no_cache" value="1" />
            {/if}

            {capture name="product_thumbnails"}
                {strip}
                    {$product_thumbnails_data = ""}
                    {$product_thumbnails_classes = "ty-product-thumbnails"}

                    {if $is_thumbnails_gallery}
                        {$is_vertical_char = "YesNo::NO"|enum}

                        {if $is_vertical}
                            {$is_vertical_char = "YesNo::YES"|enum}
                        {/if}

                        {$product_thumbnails_data = "`$product_thumbnails_data` data-ca-cycle=`$addons.ab__video_gallery.cycle`"}
                        {$product_thumbnails_data = "`$product_thumbnails_data` data-ca-vertical=`$is_vertical_char`"}
                        {$product_thumbnails_data = "`$product_thumbnails_data` data-ca-main-image-height=`$image_height`"}
                        {$product_thumbnails_classes = "`$product_thumbnails_classes` cm-image-gallery"}

                        {if $is_vertical}
                            {$product_thumbnails_classes = "`$product_thumbnails_classes` ab-vg-vertical-thumbnails"}
                        {/if}
                    {else}
                        {if $is_vertical}
                            {$product_thumbnails_classes = "`$product_thumbnails_classes` ab-vertical-thumbnails"}
                        {else}
                            {$product_thumbnails_classes = "`$product_thumbnails_classes` ty-center"}
                        {/if}
                    {/if}

                    <div class="{$product_thumbnails_classes}" {$product_thumbnails_data} id="images_preview_{$preview_id}">
                        {if $product.360_images}
                            {$image_counter = $image_counter + 1}
                            {$image_iterator = $image_iterator + 1}

                            <div class="cm-item-gallery ty-float-left">
                                <a data-ca-gallery-large-id="det_img_link_{$preview_id}_{$image_id}" {""}
                                class="cm-gallery-item cm-thumbnails-mini active ty-product-thumbnails__item" {""}
                                style="width: {$th_size}px" {""}
                                data-ca-image-order="{$image_counter}" {""}
                                data-ca-parent="#product_images_{$preview_id}"
                                >
                                    <img src="/images/360_images/{$product.product_id}/{$first_360_image.prefix}-1.{$first_360_image.extension}" width="{$th_size}" height="{$th_size}" alt="">
                                </a>
                            </div>
                        {/if}
                        
                        {call name=fn_ab__vg_get_videos_thumbs_by_pos position="$product_pos_enum::TOP"|enum}
                        {call name=fn_ab__vg_get_videos_thumbs_by_pos position="$product_pos_enum::CUSTOM"|enum}

                        {if $image_pair_var}
                            {$image_counter = $image_counter + 1}
                            {$image_iterator = $image_iterator + 1}

                            {include file="addons/ab__video_gallery/components/product_thumbnail.tpl" image=$image_pair_var image_id=$image_pair_var.image_id|default:$image_pair_var.detailed_id thumbnail_type="image"}

                            {call name=fn_ab__vg_get_videos_thumbs_by_pos position="$product_pos_enum::CUSTOM"|enum}
                        {/if}

                        {if $product.image_pairs}
                            {foreach from=$product.image_pairs item="image_pair"}
                                {$image_counter = $image_counter + 1}
                                {$image_iterator = $image_iterator + 1}

                                {if $image_pair.image_id}
                                    {assign var="img_id" value=$image_pair.image_id}
                                {else}
                                    {assign var="img_id" value=$image_pair.detailed_id}
                                {/if}

                                {include file="addons/ab__video_gallery/components/product_thumbnail.tpl" image=$image_pair image_id=$img_id thumbnail_type="image"}

                                {call name=fn_ab__vg_get_videos_thumbs_by_pos position="$product_pos_enum::CUSTOM"|enum}
                            {/foreach}
                        {/if}

                        {call name=fn_ab__vg_get_videos_thumbs_by_pos position="$product_pos_enum::CUSTOM"|enum}
                        {call name=fn_ab__vg_get_videos_thumbs_by_pos position="$product_pos_enum::BOTTOM"|enum}
                    </div>
                {/strip}
            {/capture}

            {if $is_thumbnails_gallery}
                {capture name="product_thumbnails"}
                    {$thumbnails_styles = ""}

                    {if $is_vertical && $settings.abt__device !== "mobile"}
                        {$thumbnails_styles = "`$thumbnails_styles`width: `$smarty.capture.abt__ut2_vertical_gallery_width`px;"}

                        {if $image_height_block}
                            {$thumbnails_styles = "`$thumbnails_styles`max-height: `$image_height_block`px;"}
                        {/if}
                    {else}
                        {$thumbnails_styles = "`$thumbnails_styles`height: `$th_size`px;"}
                    {/if}

                    <div class="ty-product-thumbnails_gallery{if $is_vertical && $settings.abt__device !== "mobile"} ab-vg-vertical-thumbnails{else} ab-vg-horizontal-thumbnails{/if}"{if $thumbnails_styles} style="{$thumbnails_styles}"{/if}>
                        <div class="cm-image-gallery-wrapper ty-thumbnails_gallery ty-inline-block">
                            {$smarty.capture.product_thumbnails nofilter}
                        </div>
                    </div>
                {/capture}
            {/if}

            {$smarty.capture.product_thumbnails nofilter}
        {/if}
    {/if}
</div>

{include file="common/previewer.tpl"}

{if $custom_thumbnails && !$nocarousel}
    {script src="js/addons/abt__unitheme2/abt__ut2_gallery_counter.js"}
{/if}
{if !$nocarousel}
    {script src="js/addons/ab__video_gallery/product_image_gallery.js"}
{/if}

<script src="https://scaleflex.cloudimg.io/v7/plugins/js-cloudimage-360-view/latest/js-cloudimage-360-view.min.js"></script>
<script>
(function(_, $) {
    function init360Viewers() {
        var $pendingViewers = $('.cloudimage-360:not(.initialized)');

        if (!window.CI360 || !$pendingViewers.length) {
            return;
        }

        if (typeof window.CI360.add === 'function') {
            window.CI360._viewers = window.CI360._viewers || [];

            $pendingViewers.each(function() {
                if (!this.id) {
                    this.id = 'cloudimage-360-view-' + Math.floor(Math.random() * 100000);
                }

                window.CI360.add(this.id);
            });
        } else if (typeof window.CI360.init === 'function') {
            window.CI360.init();
        } else if (typeof window.CI360.initAll === 'function') {
            window.CI360.initAll();
        }
    }

    function bind360ViewerEvents(context) {
        var $context = $(context || document);
        var $viewers = $context.is('.cloudimage-360')
            ? $context
            : $context.find('.cloudimage-360');

        $viewers.each(function() {
            $(this)
                .off('.is2orProduct360Images')
                .on('mousedown.is2orProduct360Images touchstart.is2orProduct360Images touchmove.is2orProduct360Images', function(e) {
                    e.stopPropagation();
                })
                .on('dragstart.is2orProduct360Images', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                });
        });
    }

    function sync360GalleryOrder(context) {
        var $context = $(context || document);
        var $wrappers = $context.is('.cm-preview-wrapper')
            ? $context
            : $context.find('.cm-preview-wrapper');

        $wrappers.each(function() {
            var $wrapper = $(this);
            var wrapperId = $wrapper.attr('id');

            if (!wrapperId || !$wrapper.find('.cloudimage-360').length) {
                return;
            }

            $wrapper.find('.cm-image-previewer').each(function() {
                var $previewer = $(this);
                var $owlItem = $previewer.closest('.owl-item');
                var imageOrder = $owlItem.length ? $owlItem.index() : $previewer.index();

                $previewer
                    .attr('data-ca-image-order', imageOrder)
                    .data('caImageOrder', imageOrder);
            });

            $('.cm-thumbnails-mini[data-ca-parent="#' + wrapperId + '"]', $wrapper.closest('[data-ca-previewer]')).each(function(index) {
                $(this)
                    .attr('data-ca-image-order', index)
                    .data('caImageOrder', index);
            });
        });
    }

    function boot360Viewers(context) {
        init360Viewers();
        bind360ViewerEvents(context);
        sync360GalleryOrder(context);
    }

    boot360Viewers(document);

    if (!window.is2orProduct360ImagesCommonInitBound && $.ceEvent) {
        window.is2orProduct360ImagesCommonInitBound = true;

        $.ceEvent('on', 'ce.commoninit', function(context) {
            boot360Viewers(context);
        });

        $.ceEvent('on', 'ce.product_image_gallery.ready', function() {
            boot360Viewers(document);
        });
    }
})(Tygh, Tygh.$);
</script>

{hook name="products:product_images"}{/hook}
