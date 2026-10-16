<div class="ty-rma-register">
    <form action="{""|fn_url}" method="post" name="return_registration_form" enctype="multipart/form-data">
    <input name="order_id" type="hidden" value="{$smarty.request.order_id}" />
    <input name="user_id" type="hidden" value="{$order_info.user_id}" />

    {if $actions}
        <div class="ty-rma-register__actions">
            <strong>{__("what_you_would_like_to_do")}:</strong>
            <select class="ty-rma-register__action-select" name="return_action">
                {foreach $actions as $action_id => $action}
                    <option value="{$action_id}">{$action.property}</option>
                {/foreach}
            </select>
        </div>
    {/if}

    <table class="ty-table ty-rma-register__table">
        <thead>
            <tr>
                <th class="ty-center"><input type="checkbox" name="check_all" value="Y" title="{__("check_uncheck_all")}" class="checkbox cm-check-items" /></th>
                <th>{__("product")}</th>
                <th class="ty-right">{__("price")}</th>
                <th>{__("quantity")}</th>
                <th>{__("reason")}</th>
            </tr>
        </thead>
        <tbody>
        {foreach $order_info.products as $oi}
            <tr>
                <td class="ty-center ty-rma-register-id">
                    <input type="checkbox" name="returns[{$oi.cart_id}][chosen]" id="delete_checkbox" value="Y" class="checkbox cm-item" />
                    <input type="hidden" name="returns[{$oi.cart_id}][product_id]" value="{$oi.product_id}" />
                </td>
                <td style="width: 60%" class="ty-left"><a href="{"products.view?product_id=`$oi.product_id`"|fn_url}">{$oi.product nofilter}</a>
                    {if $oi.product_options}
                        {include file="common/options_info.tpl" product_options=$oi.product_options}
                    {/if}
                </td>
                <td class="ty-right ty-nowrap">
                    {if $oi.extra.exclude_from_calculate}{__("free")}{else}{include file="common/price.tpl" value=$oi.price}{/if}
                </td>
                <td class="ty-center">
                    <input type="hidden" name="returns[{$oi.cart_id}][available_amount]" value="{$oi.amount}" />
                    <select name="returns[{$oi.cart_id}][amount]">
                    {section name="amount" loop=$oi.amount+1 start="1" step="1"}
                            <option value="{$smarty.section.amount.index}">{$smarty.section.amount.index}</option>
                    {/section}
                    </select>
                </td>
                <td class="ty-center">
                    {if $reasons}
                        <select name="returns[{$oi.cart_id}][reason]">
                        {foreach $reasons as $reason_id => $reason}
                            <option value="{$reason_id}">{$reason.property}</option>
                        {/foreach}
                        </select>
                    {/if}
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>

    <div class="ty-rma-register__comments">
        <strong class="ty-rma-register__comments-title">{__("type_comment")}</strong>
        <textarea name="comment" cols="3" rows="4" class="ty-rma-register__comments-textarea"></textarea>
    </div>

    <div class="ty-control-group" style="margin-top: 50px">
        <label class="ty-control-group__title">Evidence Photos (Max 5)</label>
        <div class="is2or-upload-container" style="margin-bottom:10px">
            <label for="is2or_photo_input" class="ty-btn ty-btn__secondary is2or-btn-upload">
                <i class="ty-icon-plus"></i> ADD PHOTOS
            </label>
            <input type="file" id="is2or_photo_input" accept="image/*" class="is2or-hidden-input" multiple hidden>
        </div>
        <div id="photo_preview_container" class="is2or-preview-wrapper"></div>
    </div>

    <div class="ty-control-group">
        <label class="ty-control-group__title">Evidence Videos (Max 3)</label>
        <div class="is2or-upload-container" style="margin-bottom:10px">
            <label for="is2or_video_input" class="ty-btn ty-btn__secondary is2or-btn-upload">
                <i class="ty-icon-plus"></i> ADD VIDEOS
            </label>
            <input type="file" id="is2or_video_input" accept="video/*" class="is2or-hidden-input" multiple hidden>
        </div>
        <div id="video_preview_container" class="is2or-preview-wrapper"></div>
    </div>

    <script>
    {literal}
    (function(_, $) {
        let photoQueue = [];
        let videoQueue = [];

        function handleFiles(inputElement, queue, containerId, limit, type, fieldName) {
            if (!inputElement) return;
            
            $(inputElement).on('change', function(e) {
                const files = Array.from(e.target.files);
                
                files.forEach(file => {
                    if (queue.length < limit) {
                        queue.push(file);
                        renderPreview(file, queue, containerId, fieldName);
                    } else {
                        $.ceNotification('show', {
                            type: 'E',
                            title: _.tr('error'),
                            message: 'Maximum ' + limit + ' ' + type + ' reached.'
                        });
                    }
                });
                this.value = ''; 
                syncHiddenInputs();
            });
        }

        function renderPreview(file, queue, containerId, fieldName) {
            const container = document.getElementById(containerId);
            const reader = new FileReader();
            const wrapper = document.createElement('div');
            wrapper.className = 'is2or-preview-item';

            reader.onload = function(e) {
                // Penggunaan backtick ` aman di sini karena sudah di dalam tag literal
                if (file.type.startsWith('image/')) {
                    wrapper.innerHTML = `<img src="${e.target.result}"><span class="remove-btn">&times;</span>`;
                } else {
                    wrapper.innerHTML = `<video src="${e.target.result}"></video><span class="remove-btn">&times;</span>`;
                }

                wrapper.querySelector('.remove-btn').onclick = function() {
                    const index = queue.indexOf(file);
                    if (index > -1) queue.splice(index, 1);
                    wrapper.remove();
                    syncHiddenInputs();
                };
                container.appendChild(wrapper);
            };
            reader.readAsDataURL(file);
        }

        function syncHiddenInputs() {
            updateFormFiles('refund_evidence_photo[]', photoQueue);
            updateFormFiles('refund_evidence_video[]', videoQueue);
        }

        function renderPreview(file, queue, containerId, fieldName) {
            const container = document.getElementById(containerId);
            const reader = new FileReader();
            const wrapper = document.createElement('div');
            wrapper.className = 'is2or-preview-item';

            reader.onload = function(e) {
                if (file.type.startsWith('image/')) {
                    wrapper.innerHTML = `<img src="${e.target.result}"><span class="remove-btn">&times;</span>`;
                } else {
                    // Tambahkan atribut 'controls' agar bisa di-play dan 'preload' agar frame muncul
                    wrapper.innerHTML = `
                        <video src="${e.target.result}" controls preload="metadata">
                            Your browser does not support the video tag.
                        </video>
                        <span class="remove-btn">&times;</span>`;
                }

                wrapper.querySelector('.remove-btn').onclick = function() {
                    const index = queue.indexOf(file);
                    if (index > -1) queue.splice(index, 1);
                    wrapper.remove();
                    syncHiddenInputs();
                };
                container.appendChild(wrapper);
            };
            reader.readAsDataURL(file);
        }

        function updateFormFiles(fieldName, queue) {
            const form = document.forms['return_registration_form'];
            if (!form) return;

            $(form).find(`input[data-queue="${fieldName}"]`).remove();

            if (queue.length === 0) return;

            const dataTransfer = new DataTransfer();
            queue.forEach(file => dataTransfer.items.add(file));
            
            const newInput = document.createElement('input');
            newInput.type = 'file';
            newInput.name = fieldName;
            newInput.multiple = true;
            newInput.style.display = 'none';
            newInput.setAttribute('data-queue', fieldName);
            newInput.files = dataTransfer.files;
            form.appendChild(newInput);
        }

        $(document).ready(function() {
            handleFiles(document.getElementById('is2or_photo_input'), photoQueue, 'photo_preview_container', 5, 'photos', 'refund_evidence_photo');
            handleFiles(document.getElementById('is2or_video_input'), videoQueue, 'video_preview_container', 3, 'videos', 'refund_evidence_video');
        });

    }(Tygh, Tygh.$));
    {/literal}
    </script>

    <div class="ty-rma-register__buttons buttons-container">
        {include file="buttons/button.tpl" but_text=__("rma.return_action") but_name="dispatch[rma.add_return]" but_meta="ty-btn__secondary cm-process-items"}
    </div>

    </form>
</div>
{capture name="mainbox_title"}{__("return_registration")}{/capture}