{if $product.360_images && $no_images}
    {$first_360_image = $product.360_images|reset}
    <div
        class="cloudimage-360"
        id="360ImagesContainer{$product.product_id}"
        data-folder="/images/360_images/{$product.product_id}/"
        data-filename-x="{$first_360_image.prefix}-{literal}{index}{/literal}.{$first_360_image.extension}"
        data-amount-x="{$product.360_images|count}"
        data-drag-speed="120"
        data-lazyload="true"
    ></div>
    <script src="https://scaleflex.cloudimg.io/v7/plugins/js-cloudimage-360-view/latest/js-cloudimage-360-view.min.js?func=proxy"></script>

    <script>
        (function() {
            if (!window.CI360 || !document.querySelector('.cloudimage-360:not(.initialized)')) {
                return;
            }

            if (typeof window.CI360.init === 'function') {
                window.CI360.init();
            } else if (typeof window.CI360.initAll === 'function') {
                window.CI360.initAll();
            }
        })();
    </script>
{/if}
