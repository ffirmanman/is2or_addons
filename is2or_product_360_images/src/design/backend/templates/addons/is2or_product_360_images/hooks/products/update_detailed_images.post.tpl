{if $product_data.360_images}
    {$first_360_image = $product_data.360_images|reset}
{else}
    {$first_360_image = []}
{/if}
<div class="control-group">
    <label class="control-label">{__("is2or_product_360_images.360_images")}:</label>
    <div class="controls">
        <div class="file-uploader">
            <div class="file-uploader__files-container clearfix ui-sortable" id="360ImagesContainer">
                {foreach $product_data.360_images as $_360_image}
                <div class="file-uploader__file ui-sortable-handle">
                    <input type="hidden" name="360_images[image_id][]" value="{$_360_image.image_id}" />
                    <div class="file-uploader__file-square">
                        <div class="file-uploader__file-section file-uploader__file-section_image" style="top: 0; bottom: 0;">
                            <img class="file-uploader__file-preview-image" alt="null" src="{$_360_image.image_path}" style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                        <div class="file-uploader__file-section file-uploader__file-section_under-image">
                            <div class="file-uploader__file-control-menu file-uploader__file-control-menu--expanded">
                                <div class="file-uploader__file-control-menu-buttons-wrapper">
                                    <a href="{$_360_image.image_path}" target="_blank" class="cm-tooltip file-uploader__file-button file-uploader__file-button-preview" title="{__("preview")}">
                                        {include_ext file="common/icon.tpl" class="icon icon-eye-open"}
                                    </a>
                                    <a class="cm-tooltip file-uploader__file-button file-uploader__file-button-delete" title="{__("remove")}">
                                        {include_ext file="common/icon.tpl" class="icon icon-trash"}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {/foreach}

                <div class="file-uploader__pickers">
                    <div class="file-uploader__file-square {if !$product_data.360_images}file-uploader__file-square--no-files{/if}">
                        <div class="file-uploader__pickers-content">
                            <p>{include_ext file="common/icon.tpl" class="icon-picture icon file-uploader__pickers-icon"}</p>
                            <p class="file-uploader__pickers-text">&nbsp;</p>
                            <div class="btn-group file-uploader__pickers-buttons">
                                <a class="btn file-uploader__pickers-buttons-select" id="360FileInputBtn" style="max-width: none !important;">
                                    {__("drop_images_select")}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="file-uploader__file ui-sortable-handle" style="display: none;">
                    <div class="file-uploader__file-square">
                        <div class="file-uploader__file-section file-uploader__file-section_image" style="top: 0; bottom: 0;">
                            <img class="file-uploader__file-preview-image" alt="null" src="" style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                        <div class="file-uploader__file-section file-uploader__file-section_under-image">
                            <div class="file-uploader__file-control-menu file-uploader__file-control-menu--expanded">
                                <div class="file-uploader__file-control-menu-buttons-wrapper">
                                    <a href="" target="_blank" class="cm-tooltip file-uploader__file-button file-uploader__file-button-preview" title="{__("preview")}">
                                        {include_ext file="common/icon.tpl" class="icon icon-eye-open"}
                                    </a>
                                    <a class="cm-tooltip file-uploader__file-button file-uploader__file-button-delete" title="{__("remove")}">
                                        {include_ext file="common/icon.tpl" class="icon icon-trash"}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <input type="file" multiple id="360FileInput" style="display: none;" accept="image/*" />
        </div>

        <p class="muted description">{__("is2or_product_360_images.360_images_tooltip")}</p>
    </div>
</div>

<script>
(function(_, $) {
    _.tr({
        'is2or_product_360_images_mismatch_extension': '{__('is2or_product_360_images.mismatch_extension')}',
        'is2or_product_360_images_only_image_allowed': '{__('is2or_product_360_images.only_image_allowed')}'
    });

    const existExt = '{$first_360_image.extension|default:''}';
    function initSortable() {
        const $container = $('#360ImagesContainer');

        $container.sortable({
            tolerance: 'pointer',
            containment: $container,
            cursor: 'move',
            placeholder: 'file-uploader__sortable-placeholder',
            forceHelperSize: true,
            axis: 'xy',
            items: '.file-uploader__file',
            update: function() {
                console.log('Files reordered');
            }
        });
    }

    $(document).on('click', '#360FileInputBtn', function() {
        $('#360FileInput').trigger('click');
    });

    $(document).on('change', '#360FileInput', function(e) {
        const fileList = e.target.files;
        const files = (typeof Array.from === 'function') 
            ? Array.from(fileList) 
            : Array.prototype.slice.call(fileList);

        if (!files.length) return;

        const firstExt = existExt.length
            ? existExt
            : files[0].name.split('.').pop().toLowerCase();
        // check all files have the same extension
        const allExtsMatch = files.every(file => file.name.split('.').pop().toLowerCase() === firstExt);
        if (!allExtsMatch) {
            $.ceNotification('show', {
                type: 'E',
                title: _.tr('error'),
                message: _.tr('is2or_product_360_images_mismatch_extension'),
            });
            $(this).val('');
            return;
        }

        // only allow certain extensions
        const allowed = ['jpg','jpeg','png','webp'];
        if (allowed.indexOf(firstExt) === -1) {
            $.ceNotification('show', {
                type: 'E',
                title: _.tr('error'),
                message: _.tr('is2or_product_360_images_only_image_allowed'),
            })
            $(this).val('');
            return;
        }

        const container = $(document).find('#360ImagesContainer');
        const picker = container.find('.file-uploader__pickers');
        
        for (const file of files) {
            const el = container.find('.file-uploader__file:hidden').first().clone();
            el.show();

            const reader = new FileReader();

            reader.onload = function(evt) {
                const url = evt.target.result;

                // set image + href before inserting into DOM
                el.find('.file-uploader__file-preview-image').attr('src', url);
                el.find('.file-uploader__file-button-preview')
                    .attr('href', url)
                    .attr('target', '_blank')

                // insert the prepared element
                const previewBtn = el.find('.file-uploader__file-button-preview');
                previewBtn.off('click').on('click', function(e) {
                    e.preventDefault();

                    // open the image directly in a new tab safely
                    const win = window.open();
                    win.document.write('<img src="' + url + '" style="max-width:100%;display:block;margin:auto;">');
                    win.document.title = 'Preview';
                });

                // Create hidden file input
                const fileInput = $('<input>', {
                    type: 'file',
                    name: '360_images[]',
                    style: 'display: none;',
                });

                // attach file object using DataTransfer API
                const dt = new DataTransfer();
                dt.items.add(file);
                fileInput[0].files = dt.files;

                el.append(fileInput);
                el.prepend('<input type="hidden" name="360_images[image_id][]" value="0" />');
                picker.before(el);
            };
            reader.readAsDataURL(file);
        }

        // Reinit sortable
        initSortable();

        $(this).val('');
    });

    $(document).on('click', '#360ImagesContainer .file-uploader__file-button-delete', function(e) {
        e.preventDefault();
        if (confirm(_.tr('text_are_you_sure_to_proceed'))) {
            const $file = $(this).closest('.file-uploader__file');
            const imageIdInput = $file.find('input[name="360_images[image_id][]"]');
            const imageId = imageIdInput.val();

            if (imageId) {
                $('#360ImagesContainer').append('<input type="hidden" name="deleted_360_images[]" value="' + imageId + '" />');
            }
            $file.fadeOut(200, function() { $(this).remove(); });
        }
    });

    initSortable();
})(Tygh, Tygh.$);
</script>