{$is2or_pairs = (($product_data.main_pair) ? [$product_data.main_pair] : []) + $product_data.image_pairs|default:[]}

{if $is2or_pairs && $product_data.product_id}
<div class="control-group is2or-tidy-photo-panel" data-ca-is2or-product-id="{$product_data.product_id}">
    <label class="control-label">{__("is2or_ai_integration.tidy_photo_title")}:</label>
    <div class="controls">
        {foreach from=$is2or_pairs item=pair}
            {$is2or_thumbnail = $pair|fn_image_to_display:60:60}
            {$is2or_detailed = $pair.detailed|fn_image_to_display:0:0}

            <div class="is2or-tidy-row"
                data-ca-pair-id="{$pair.pair_id}"
                data-ca-image-url="{$is2or_detailed.image_path|default:$is2or_detailed.absolute_path}"
                style="display:inline-block;vertical-align:top;margin:0 12px 12px 0;text-align:center;">

                <div>
                    <img src="{$is2or_thumbnail.image_path|default:$is2or_thumbnail.absolute_path|default:$is2or_detailed.image_path|default:$is2or_detailed.absolute_path}" 
                         width="60" 
                         height="60" 
                         alt="" 
                         class="is2or-tidy-before" 
                         style="object-fit: contain; background: #f5f5f5;" />
                         
                    <img src="" 
                         width="60" 
                         height="60" 
                         alt="" 
                         class="is2or-tidy-after" 
                         style="display:none; object-fit: contain; background: #f5f5f5;" />
                </div>

                <div class="is2or-tidy-actions" style="margin-top:4px;">
                    <a class="btn btn-mini is2or-tidy-start-btn" href="javascript:void(0);">{__("is2or_ai_integration.tidy_photo")}</a>
                    <a class="btn btn-mini btn-success is2or-tidy-use-btn" href="javascript:void(0);" style="display:none;">{__("is2or_ai_integration.use_this")}</a>
                    <a class="btn btn-mini is2or-tidy-keep-btn" href="javascript:void(0);" style="display:none;">{__("is2or_ai_integration.keep_original")}</a>
                </div>
                <div class="is2or-tidy-status muted" style="font-size:11px;margin-top:2px;"></div>
            </div>
        {/foreach}
    </div>
</div>

<script type="text/javascript">
(function ($) {
    "use strict";

    var is2orAiTidy = {
        urls: {
            start: "{"is2or_ai_integration.image_cleanup_start"|fn_url}",
            poll:  "{"is2or_ai_integration.image_cleanup_poll"|fn_url}",
            apply: "{"is2or_ai_integration.image_cleanup_apply"|fn_url}",
            reject: "{"is2or_ai_integration.image_cleanup_reject"|fn_url}"
        },
        getCsrf: function () {
            if (window.Tygh && Tygh.security_hash) {
                return Tygh.security_hash;
            } else if (window.Tygh && Tygh.csrf_token) {
                return Tygh.csrf_token;
            }
            return $('input[name="security_hash"]').val() || '';
        }
    };

    function poll(row, job_id, onDone) {
        $.ajax({
            url: is2orAiTidy.urls.poll,
            type: 'GET',
            dataType: 'json',
            data: { job_id: job_id }
        }).done(function (response) {
            if (!response || !response.success) {
                row.find('.is2or-tidy-status').text('{__("is2or_ai_integration.error_bad_response")|escape:javascript}');
                row.find('.is2or-tidy-start-btn').prop('disabled', false);
                return;
            }
            if (response.status === 'done') {
                onDone(response);
                return;
            }
            if (response.status === 'failed') {
                row.find('.is2or-tidy-status').text('{__("is2or_ai_integration.tidy_failed")|escape:javascript}');
                row.find('.is2or-tidy-start-btn').prop('disabled', false);
                return;
            }
            setTimeout(function () { poll(row, job_id, onDone); }, 2000);
        }).fail(function () {
            row.find('.is2or-tidy-status').text('{__("is2or_ai_integration.error_bad_response")|escape:javascript}');
            row.find('.is2or-tidy-start-btn').prop('disabled', false);
        });
    }

    $(document).on('click', '.is2or-tidy-start-btn', function (e) {
        e.preventDefault();

        var $btn = $(this);
        var row = $btn.closest('.is2or-tidy-row');
        var panel = $btn.closest('.is2or-tidy-photo-panel');
        var product_id = panel.data('ca-is2or-product-id');
        var pair_id = row.data('ca-pair-id');
        var image_url = row.data('ca-image-url');

        $btn.prop('disabled', true);
        row.find('.is2or-tidy-status').text('{__("is2or_ai_integration.tidying")|escape:javascript}');

        $.ajax({
            url: is2orAiTidy.urls.start,
            type: 'POST',
            dataType: 'json',
            data: {
                product_id: product_id,
                pair_id: pair_id,
                image_url: image_url,
                security_hash: is2orAiTidy.getCsrf()
            }
        }).done(function (response) {
            if (!response || !response.success) {
                row.find('.is2or-tidy-status').text('{__("is2or_ai_integration.error_bad_response")|escape:javascript}');
                $btn.prop('disabled', false);
                return;
            }

            poll(row, response.job_id, function (done_response) {
                var result = (done_response.results || [])[0];
                
                if (!result || result.status !== 'done') {
                    row.find('.is2or-tidy-status').text('{__("is2or_ai_integration.tidy_failed")|escape:javascript}');
                    $btn.prop('disabled', false);
                    return;
                }

                $.ajax({
                    url: is2orAiTidy.urls.poll,
                    type: 'GET',
                    dataType: 'json',
                    data: { job_id: response.job_id, include_image: 1 }
                }).done(function (preview_response) {
                    var preview = (preview_response.results || [])[0];
                    if (!preview || !preview.image_base64) {
                        row.find('.is2or-tidy-status').text('{__("is2or_ai_integration.tidy_failed")|escape:javascript}');
                        $btn.prop('disabled', false);
                        return;
                    }

                    row.data('ca-job-id', response.job_id);
                    row.data('ca-suggestion-id', result.suggestion_id);

                    row.find('.is2or-tidy-after')
                        .attr('src', 'data:' + preview.mime + ';base64,' + preview.image_base64)
                        .show();

                    row.find('.is2or-tidy-status').text('{__("is2or_ai_integration.tidy_ready")|escape:javascript}');
                    row.find('.is2or-tidy-start-btn').hide();
                    row.find('.is2or-tidy-use-btn, .is2or-tidy-keep-btn').show();
                });
            });
        }).fail(function (jqXHR, textStatus, errorThrown) {
            row.find('.is2or-tidy-status').text('{__("is2or_ai_integration.error_bad_response")|escape:javascript}');
            $btn.prop('disabled', false);
        });
    });

    $(document).on('click', '.is2or-tidy-use-btn', function (e) {
        e.preventDefault();

        var $btn = $(this);
        var row = $btn.closest('.is2or-tidy-row');
        var panel = $btn.closest('.is2or-tidy-photo-panel');
        var product_id = panel.data('ca-is2or-product-id');
        var pair_id = row.data('ca-pair-id');
        var job_id = row.data('ca-job-id');
        var suggestion_id = row.data('ca-suggestion-id');

        row.find('.is2or-tidy-use-btn, .is2or-tidy-keep-btn').prop('disabled', true);
        row.find('.is2or-tidy-status').text('{__("is2or_ai_integration.saving")|escape:javascript}');

        $.ajax({
            url: is2orAiTidy.urls.apply,
            type: 'POST',
            dataType: 'json',
            data: {
                product_id: product_id,
                pair_id: pair_id,
                job_id: job_id,
                suggestion_id: suggestion_id,
                security_hash: is2orAiTidy.getCsrf()
            }
        }).done(function (response) {
            if (!response || !response.success) {
                row.find('.is2or-tidy-status').text('{__("is2or_ai_integration.error_bad_response")|escape:javascript}');
                row.find('.is2or-tidy-use-btn, .is2or-tidy-keep-btn').prop('disabled', false);
                return;
            }
            window.location.reload();
        }).fail(function () {
            row.find('.is2or-tidy-status').text('{__("is2or_ai_integration.error_bad_response")|escape:javascript}');
            row.find('.is2or-tidy-use-btn, .is2or-tidy-keep-btn').prop('disabled', false);
        });
    });

    $(document).on('click', '.is2or-tidy-keep-btn', function (e) {
        e.preventDefault();

        var $btn = $(this);
        var row = $btn.closest('.is2or-tidy-row');
        var suggestion_id = row.data('ca-suggestion-id');

        $.ajax({
            url: is2orAiTidy.urls.reject,
            type: 'POST',
            dataType: 'json',
            data: { 
                suggestion_id: suggestion_id,
                security_hash: is2orAiTidy.getCsrf()
            }
        }).always(function () {
            row.find('.is2or-tidy-after').hide();
            row.find('.is2or-tidy-use-btn, .is2or-tidy-keep-btn').hide();
            row.find('.is2or-tidy-start-btn').show().prop('disabled', false);
            row.find('.is2or-tidy-status').text('');
        });
    });

})(jQuery);
</script>
{/if}