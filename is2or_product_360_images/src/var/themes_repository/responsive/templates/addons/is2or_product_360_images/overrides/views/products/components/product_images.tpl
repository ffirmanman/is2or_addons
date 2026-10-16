{assign var="th_size" value=$thumbnails_size|default:35}

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

{if !$preview_id}
    {$preview_id = $product.product_id}
{/if}

<div class="ty-product-img cm-preview-wrapper" id="product_images_{$preview_id}">
    {if $product.360_images}
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

    {include file="common/image.tpl" obj_id="`$preview_id`_`$image_id`" images=$image_pair_var link_class="cm-image-previewer" image_width=$image_width image_height=$image_height image_id="preview[product_images_`$preview_id`]"}

    {foreach from=$product.image_pairs item="image_pair"}
        {if $image_pair}
            {if $image_pair.image_id}
                {assign var="img_id" value=$image_pair.image_id}
            {else}
                {assign var="img_id" value=$image_pair.detailed_id}
            {/if}
            {include file="common/image.tpl" images=$image_pair link_class="cm-image-previewer hidden" obj_id="`$preview_id`_`$img_id`" image_width=$image_width image_height=$image_height image_id="preview[product_images_`$preview_id`]"}
        {/if}
    {/foreach}
</div>

{if $product.image_pairs || $product.360_images}
    {if $settings.Appearance.thumbnails_gallery == "Y"}
        {$image_counter = 0}
        <input type="hidden" name="no_cache" value="1" />
        {strip}
        <div class="ty-center ty-product-bigpicture-thumbnails_gallery">
            <div class="cm-image-gallery-wrapper ty-thumbnails_gallery ty-inline-block">
                {strip}
                <div class="ty-product-thumbnails owl-carousel cm-image-gallery" id="images_preview_{$preview_id}">
                    {if $product.360_images}
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

                        {$image_counter = $image_counter + 1}
                    {/if}

                    {if $image_pair_var}
                        <div class="cm-item-gallery ty-float-left">
                            <a data-ca-gallery-large-id="det_img_link_{$preview_id}_{$image_id}" {""}
                               class="cm-gallery-item cm-thumbnails-mini {if !$product.360_images}active{/if} ty-product-thumbnails__item" {""}
                               style="width: {$th_size}px" {""}
                               data-ca-image-order="{$image_counter}" {""}
                               data-ca-parent="#product_images_{$preview_id}"
                            >
                           {include file="common/image.tpl" images=$image_pair_var image_width=$th_size image_height=$th_size show_detailed_link=false obj_id="`$preview_id`_`$image_id`_mini"}
                            </a>
                        </div>
                    {/if}
                    {if $product.image_pairs}
                        {foreach from=$product.image_pairs item="image_pair"}
                            {$image_counter = $image_counter + 1}
                            {if $image_pair}
                                <div class="cm-item-gallery ty-float-left">
                                    {if $image_pair.image_id}
                                        {assign var="img_id" value=$image_pair.image_id}
                                    {else}
                                        {assign var="img_id" value=$image_pair.detailed_id}
                                    {/if}
                                    <a data-ca-gallery-large-id="det_img_link_{$preview_id}_{$img_id}" {""}
                                       class="cm-gallery-item cm-thumbnails-mini ty-product-thumbnails__item" {""}
                                       data-ca-image-order="{$image_counter}" {""}
                                       data-ca-parent="#product_images_{$preview_id}"
                                    >
                                    {include file="common/image.tpl" images=$image_pair image_width=$th_size image_height=$th_size show_detailed_link=false obj_id="`$preview_id`_`$img_id`_mini"}
                                    </a>
                                </div>
                            {/if}
                        {/foreach}
                    {/if}
                </div>
                {/strip}
            </div>
        </div>
        {/strip}
    {else}
        {$image_counter = 0}
        <div class="ty-product-thumbnails ty-center cm-image-gallery" id="images_preview_{$preview_id}" style="width: {$image_width}px;">
            {strip}
                {if $product.360_images}
                    <a data-ca-gallery-large-id="det_img_link_{$preview_id}_{$image_id}" {""}
                            class="cm-thumbnails-mini active ty-product-thumbnails__item" {""}
                            data-ca-image-order="{$image_counter}" {""}
                            data-ca-parent="#product_images_{$preview_id}"
                    >
                        <img src="/images/360_images/{$product.product_id}/{$first_360_image.prefix}-1.{$first_360_image.extension}" width="{$th_size}" height="{$th_size}" alt="">
                    </a>

                    {$image_counter = $image_counter + 1}
                {/if}

                {if $image_pair_var}
                <a data-ca-gallery-large-id="det_img_link_{$preview_id}_{$image_id}" {""}
                        class="cm-thumbnails-mini {if !$product.360_images}active{/if} ty-product-thumbnails__item" {""}
                        data-ca-image-order="{$image_counter}" {""}
                        data-ca-parent="#product_images_{$preview_id}"
                >
                    {include file="common/image.tpl" images=$image_pair_var image_width=$th_size image_height=$th_size show_detailed_link=false obj_id="`$preview_id`_`$image_id`_mini"}
                </a>
                {/if}

                {if $product.image_pairs}
                    {foreach from=$product.image_pairs item="image_pair"}
                        {$image_counter = $image_counter + 1}
                        {if $image_pair}
                                {if $image_pair.image_id == 0}
                                    {assign var="img_id" value=$image_pair.detailed_id}
                                {else}
                                    {assign var="img_id" value=$image_pair.image_id}
                                {/if}
                                <a data-ca-gallery-large-id="det_img_link_{$preview_id}_{$img_id}" {""}
                                        class="cm-thumbnails-mini ty-product-thumbnails__item" {""}
                                        data-ca-image-order="{$image_counter}" {""}
                                        data-ca-parent="#product_images_{$preview_id}"
                                >
                                {include file="common/image.tpl" images=$image_pair image_width=$th_size image_height=$th_size show_detailed_link=false obj_id="`$preview_id`_`$img_id`_mini"}
                                </a>
                        {/if}
                    {/foreach}
                {/if}
            {/strip}
        </div>
    {/if}
{/if}


{include file="common/previewer.tpl"}
{script src="js/tygh/product_image_gallery.js"}
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
