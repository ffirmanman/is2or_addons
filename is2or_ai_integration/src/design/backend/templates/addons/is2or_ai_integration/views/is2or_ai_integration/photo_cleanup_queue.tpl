{capture name="mainbox"}
    <div id="update_photo_cleanup_queue">
        <form action="{""|fn_url}" method="post" name="photo_cleanup_form">
            <input type="hidden" name="dispatch" value="is2or_ai_integration.photo_cleanup_queue" />

            {include file="common/pagination.tpl" save_current_page=true}

            {if $photo_cleanup_rows}
                <table width="100%" class="table table-middle">
                    <thead>
                        <tr>
                            <th>{__("product")}</th>
                            <th width="15%">{__("is2or_ai_integration.preview")}</th>
                            <th width="20%">{__("date")}</th>
                            <th width="15%" class="right">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                    {foreach from=$photo_cleanup_rows item="row"}
                        <tr id="photo_cleanup_row_{$row.id}"
                            data-ca-suggestion-id="{$row.id}"
                            data-ca-product-id="{$row.output.product_id}"
                            data-ca-pair-id="{$row.output.pair_id|default:0}"
                            data-ca-job-id="{$row.output.job_id}">

                            <td>
                                {if $row.product_id}
                                    <a href="{"products.update?product_id=`$row.product_id`"|fn_url}" target="_blank">
                                        {$row.product_name|default:"#`$row.product_id`"}
                                    </a>
                                    <div class="muted small">ID: {$row.product_id}</div>
                                {else}
                                    <span class="muted">&mdash;</span>
                                {/if}
                            </td>

                            <td>
                                <div class="is2or-queue-preview">
                                    <a href="javascript:void(0);" class="btn btn-mini is2or-queue-preview-btn">{__("is2or_ai_integration.load_preview")}</a>
                                    <div class="is2or-queue-preview-images" style="display:none; margin-top: 6px;">
                                        <img src="" width="60" height="60" class="is2or-queue-preview-img" alt="" style="object-fit: contain; background: #f5f5f5; border: 1px solid #e3e3e3; padding: 2px;" />
                                    </div>
                                    <div class="is2or-queue-preview-status muted" style="font-size:11px; margin-top: 4px;"></div>
                                </div>
                            </td>

                            <td>
                                {$row.created_at|default:$row.date|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                            </td>

                            <td class="right nowrap">
                                {if $row.can_apply}
                                    <a class="btn btn-primary is2or-queue-approve-btn" href="javascript:void(0);">{__("approve")}</a>
                                    <a class="btn text-error is2or-queue-reject-btn" href="javascript:void(0);">{__("reject")}</a>
                                {else}
                                    <span class="muted small" style="margin-right: 10px;">{__("is2or_ai_integration.tidy_no_pair")}</span>
                                    <a class="btn text-error is2or-queue-reject-btn" href="javascript:void(0);">{__("reject")}</a>
                                {/if}
                            </td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            {else}
                <p class="no-items">{__("no_data")}</p>
            {/if}

            {include file="common/pagination.tpl"}
        </form>
    <!--update_photo_cleanup_queue--></div>
{/capture}

{include file="common/mainbox.tpl" title=__("is2or_ai_integration.photo_cleanup_queue") content=$smarty.capture.mainbox}

<script type="text/javascript">
(function ($) {
    "use strict";

    // Helper untuk mengambil token keamanan CS-Cart
    function getCsrfToken() {
        if (window.Tygh && Tygh.security_hash) {
            return Tygh.security_hash;
        } else if (window.Tygh && Tygh.csrf_token) {
            return Tygh.csrf_token;
        }
        return $('input[name="security_hash"]').val() || '';
    }

    $(document).on('click', '.is2or-queue-preview-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var row = $btn.closest('tr');
        var job_id = row.data('ca-job-id');

        $btn.hide();
        row.find('.is2or-queue-preview-status').text('{__("is2or_ai_integration.loading")|escape:javascript}');

        // GET request tidak membutuhkan token CSRF
        $.ajax({
            url: "{"is2or_ai_integration.photo_cleanup_preview"|fn_url}",
            type: 'GET',
            dataType: 'json',
            data: { job_id: job_id }
        }).done(function (response) {
            if (!response || !response.success) {
                row.find('.is2or-queue-preview-status').text(
                    (response && response.error) || '{__("is2or_ai_integration.tidy_expired")|escape:javascript}'
                );
                return;
            }
            row.find('.is2or-queue-preview-img').attr('src', 'data:' + response.mime + ';base64,' + response.image);
            row.find('.is2or-queue-preview-images').fadeIn('fast');
            row.find('.is2or-queue-preview-status').text('');
            if (response.pair_id) {
                row.data('ca-pair-id', response.pair_id);
            }
        }).fail(function () {
            row.find('.is2or-queue-preview-status').text('{__("is2or_ai_integration.error_bad_response")|escape:javascript}');
        });
    });

    $(document).on('click', '.is2or-queue-approve-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var row = $btn.closest('tr');

        // Nonaktifkan tombol sementara
        row.find('.btn').addClass('disabled');
        row.find('.is2or-queue-preview-status').text('{__("is2or_ai_integration.saving")|escape:javascript}');

        $.ajax({
            url: "{"is2or_ai_integration.photo_cleanup_approve"|fn_url}",
            type: 'POST',
            dataType: 'json',
            data: {
                suggestion_id: row.data('ca-suggestion-id'),
                product_id: row.data('ca-product-id'),
                pair_id: row.data('ca-pair-id'),
                job_id: row.data('ca-job-id'),
                security_hash: getCsrfToken() // WAJIB ada untuk POST
            }
        }).done(function (response) {
            if (!response || !response.success) {
                row.find('.is2or-queue-preview-status').text(
                    (response && response.error) || '{__("is2or_ai_integration.error_bad_response")|escape:javascript}'
                );
                row.find('.btn').removeClass('disabled');
                return;
            }
            row.fadeOut(300, function () { $(this).remove(); });
        }).fail(function () {
            row.find('.is2or-queue-preview-status').text('{__("is2or_ai_integration.error_bad_response")|escape:javascript}');
            row.find('.btn').removeClass('disabled');
        });
    });

    $(document).on('click', '.is2or-queue-reject-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var row = $btn.closest('tr');

        row.find('.btn').addClass('disabled');

        $.ajax({
            url: "{"is2or_ai_integration.photo_cleanup_reject"|fn_url}",
            type: 'POST',
            dataType: 'json',
            data: { 
                suggestion_id: row.data('ca-suggestion-id'),
                security_hash: getCsrfToken() // WAJIB ada untuk POST
            }
        }).always(function () {
            row.fadeOut(300, function () { $(this).remove(); });
        });
    });

})(jQuery);
</script>