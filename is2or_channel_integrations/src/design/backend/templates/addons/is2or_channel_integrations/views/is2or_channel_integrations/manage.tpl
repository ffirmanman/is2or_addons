{capture name="mainbox"}

    <div class="alert alert-info">
        <p>
            <strong>{__("note")}:</strong>
            {__("is2or_channel_integrations.integration_page_description")}
        </p>
    </div>

    <div class="row-fluid">

        {* Salla Integration Card *}
        <div class="span6"
             style="border:1px solid #e5e5e5; padding:20px; border-radius:8px; background:#fff;">

            <div class="row-fluid"
                 style="display:flex; align-items:center; justify-content:space-between;">

                <div>
                    <h3 style="margin:0 0 5px 0;">
                        {__("is2or_channel_integrations.salla_channel")}
                    </h3>

                    <p style="color:#666; margin:0;">
                        {__("is2or_channel_integrations.salla_channel_description")}
                    </p>
                </div>

                <div>
                    {if $salla_status === 'active'}
                        <span class="label label-success"
                            style="padding:6px 12px; font-size:12px;">

                            {__("is2or_channel_integrations.connected")}
                        </span>

                        <div class="control-group">
                            <label class="control-label">
                                {__("is2or_channel_integrations.initial_sync")}:
                            </label>

                            <div class="controls">

                                <button type="button"
                                        class="btn btn-info"
                                        id="btn_import_salla">

                                    <i class="icon-download-alt"
                                       style="vertical-align:middle; margin-right:6px;"></i>

                                    <span style="vertical-align:middle;">
                                        {__("is2or_channel_integrations.import_products_from_salla")}
                                    </span>
                                </button>

                                <p class="muted description">
                                    {__("is2or_channel_integrations.import_products_description")}
                                </p>

                                <script type="text/javascript">
                                (function(_, $) {
                                    $(document).ready(function() {
                                        $('#btn_import_salla').on('click', function() {
                                            var btn = $(this);
                                            btn.prop('disabled', true)
                                            .find('span').text('{__("is2or_channel_integrations.loading")}');
                                            $.ajax({
                                                url: fn_url('is2or_channel_integrations.trigger_import&is_ajax=1'),
                                                type: 'POST',
                                                dataType: 'json',
                                                data: {
                                                    platform: 'salla',
                                                    security_hash: _.security_hash
                                                },
                                                success: function(response) {
                                                    var data = response;
                                                    if (response && typeof response.text === 'string') {
                                                        try {
                                                            data = JSON.parse(response.text);
                                                        } catch(e) {}
                                                    }
                                                    btn.prop('disabled', false)
                                                    .find('span').text('{__("is2or_channel_integrations.import_products_from_salla")}');
                                                    if (data && data.error === false) {
                                                        $.ceNotification('show', {
                                                            type: 'N',
                                                            title: '{__("notice")}',
                                                            message: 'Data synchronization has been triggered successfully. Products will be imported gradually in the background.'
                                                        });
                                                    } else if (data && data.error) {
                                                        $.ceNotification('show', {
                                                            type: 'E',
                                                            title: '{__("error")}',
                                                            message: data.message || '{__("is2or_channel_integrations.internal_server_error")}'
                                                        });
                                                    } else {
                                                        $.ceNotification('show', {
                                                            type: 'W',
                                                            title: '{__("warning")}',
                                                            message: 'Unexpected response from server.'
                                                        });
                                                    }
                                                },
                                                error: function(xhr, status, error) {
                                                    btn.prop('disabled', false)
                                                    .find('span').text('{__("is2or_channel_integrations.import_products_from_salla")}');
                                                    $.ceNotification('show', {
                                                        type: 'E',
                                                        title: '{__("error")}',
                                                        message: '{__("is2or_channel_integrations.internal_server_error")}'
                                                    });
                                                    console.error(xhr.responseText);
                                                }
                                            });
                                        });

                                        $('#sync_history_tbody').on('click', '.btn-generate-category', function() {
                                            var btn = $(this);
                                            btn.prop('disabled', true).text('Generating...');

                                            $.ajax({
                                                url: fn_url('is2or_channel_integrations.generate_category&is_ajax=1'),
                                                type: 'POST',
                                                dataType: 'json',
                                                timeout: 360000,
                                                data: {
                                                    security_hash: _.security_hash
                                                },
                                                success: function(response) {
                                                    var data = response;
                                                    if (response && typeof response.text === 'string') {
                                                        try { data = JSON.parse(response.text); } catch(e) {}
                                                    }

                                                    // Tampilkan debug log di console, apapun hasilnya (sukses/error)
                                                    if (data && data.debug_log) {
                                                        console.group('is2or generate_category debug log');
                                                        data.debug_log.forEach(function(line) {
                                                            console.log(line);
                                                        });
                                                        console.groupEnd();
                                                    }

                                                    if (data && data.error === false) {
                                                        btn.prop('disabled', false).text('Generate Category').removeClass('btn-primary').addClass('btn-success');
                                                        $.ceNotification('show', {
                                                            type: 'N',
                                                            title: '{__("notice")}',
                                                            message: data.message || 'Generate AI categories completed.'
                                                        });
                                                    } else {
                                                        btn.prop('disabled', false).text('Generate Category');
                                                        $.ceNotification('show', {
                                                            type: 'E',
                                                            title: '{__("error")}',
                                                            message: (data && data.message) || 'Generate process failed.'
                                                        });
                                                    }
                                                },
                                                error: function(xhr, status, error) {
                                                    btn.prop('disabled', false).text('Generate Category');
                                                    $.ceNotification('show', { type: 'E', title: '{__("error")}', message: '{__("is2or_channel_integrations.internal_server_error")}' });

                                                    console.error("AJAX/Network Error: ", status, error);
                                                    console.error("Response Text: ", xhr.responseText);

                                                    // Coba parse responseText siapa tahu ada debug_log meski status HTTP error
                                                    if (xhr.responseText) {
                                                        try {
                                                            var errData = JSON.parse(xhr.responseText);
                                                            if (errData.debug_log) {
                                                                console.group('is2or generate_category debug log (from error response)');
                                                                errData.debug_log.forEach(function(line) {
                                                                    console.log(line);
                                                                });
                                                                console.groupEnd();
                                                            }
                                                        } catch(e) {
                                                            // responseText bukan JSON valid, tidak ada yang bisa di-parse
                                                        }
                                                    }
                                                }
                                            });
                                        });
                                    });
                                })(Tygh, Tygh.$);
                                </script>

                            </div>
                        </div>
                    {else}
                        <span class="label label-important" style="padding: 6px 12px; font-size: 12px;">{__("is2or_channel_integrations.disconnected")}</span>
                    {/if}
                </div>
            </div>

            <hr style="margin:15px 0;">

            <div class="buttons-container">

                {if $salla_status !== 'active'}

                    <button type="button"
                            class="btn btn-primary"
                            id="btn_connect_salla">
                        {__("is2or_channel_integrations.connect_salla")}
                    </button>

                    <script type="text/javascript">
                    (function(_, $) {

                        $(document).ready(function() {

                            $('#btn_connect_salla').on('click', function() {

                                var btn = $(this);

                                btn.prop('disabled', true)
                                .text('{__("is2or_channel_integrations.loading")}');

                                $.ajax({
                                    url: fn_url('is2or_channel_integrations.request_authorize_url&is_ajax=1'),
                                    type: 'POST',
                                    dataType: 'json',
                                    data: {
                                        security_hash: _.security_hash
                                    },

                                    success: function(response) {

                                        btn.prop('disabled', false)
                                        .text('{__("is2or_channel_integrations.connect_salla")}');

                                        var data = response;
                                        if (response && typeof response.text === 'string') {
                                            try { data = JSON.parse(response.text); } catch(e) {}
                                        }

                                        if (data && data.authorization_url) {
                                            window.location.href = data.authorization_url;
                                        } else {
                                            alert(data.message || '{__("is2or_channel_integrations.failed_get_authorize_url")}');
                                        }
                                    },

                                    error: function(xhr, status, error) {

                                        btn.prop('disabled', false)
                                        .text('{__("is2or_channel_integrations.connect_salla")}');

                                        alert('{__("is2or_channel_integrations.internal_server_error")}');

                                        console.error(xhr.responseText);
                                    }
                                });
                            });
                        });

                    })(Tygh, Tygh.$);
                    </script>

                {else}
                    <!-- Tombol Sync History -->
                    <button type="button" class="btn btn-secondary" id="btn_sync_history">
                        <i class="icon-time" style="margin-right:5px;"></i>
                        {__("is2or_channel_integrations.sync_history")}
                    </button>
                    
                    <!-- Tombol Disconnect dengan konfirmasi pop-up native CS-Cart -->
                    <a href="{"is2or_channel_integrations.disconnect?company_id=`$company_id`"|fn_url}" class="btn btn-danger cm-confirm">
                        {__("is2or_channel_integrations.disconnect")}
                    </a>
                {/if}

            </div>

            {* Sync History Modal *}
            <div id="modal_sync_history"
                style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
                        background:rgba(0,0,0,0.5); z-index:9999; overflow-y:auto;">

                <div style="background:#fff; width:780px; margin:60px auto; border-radius:8px;
                            padding:24px; position:relative;">

                    <button type="button" id="btn_close_history"
                            style="position:absolute; top:12px; right:16px; background:none;
                                border:none; font-size:20px; cursor:pointer;">&#x2715;</button>

                    <h4 style="margin:0 0 16px 0;">
                        {__("is2or_channel_integrations.sync_history")} — Salla
                    </h4>

                    <div id="sync_history_loading" style="text-align:center; padding:30px; color:#999;">
                        <i class="icon-spinner icon-spin"></i> Loading...
                    </div>

                    <div id="sync_history_empty" style="display:none; text-align:center; padding:30px; color:#999;">
                        No sync history found.
                    </div>

                    <div id="sync_history_content" style="display:none; max-height:480px; overflow-y:auto;">
                        <table class="table table-striped table-bordered" style="width:100%; font-size:13px;">
                            <thead>
                                <tr>
                                    <th style="width:40px;"></th>
                                    <th>Job ID</th>
                                    <th>Status</th>
                                    <th>Attempt</th>
                                    <th>Success</th>
                                    <th>Failed</th>
                                    <th>Completed At</th>
                                    <th>Sync Time</th>
                                    <th>Category</th>
                                </tr>
                            </thead>
                            <tbody id="sync_history_tbody"></tbody>
                        </table>
                    </div>

                    <div id="sync_history_error" style="display:none; color:#b94a48; padding:10px 0;"></div>
                </div>
            </div>

            <script type="text/javascript">
            (function(_, $) {

                $(document).ready(function() {

                    function formatTimestamp(ts) {
                        if (!ts) return '—';
                        var d = new Date(parseInt(ts) * 1000);
                        return d.toLocaleString();
                    }

                    function statusBadge(status) {
                        var color = {
                            'pending'   : '#f89406',
                            'succeeded' : '#468847',
                            'failed'    : '#b94a48',
                            'rejected'  : '#b94a48'
                        };
                        var bg = color[status] || '#999';
                        return '<span style="background:' + bg + '; color:#fff; padding:2px 8px;'
                            + ' border-radius:3px; font-size:11px;">' + status + '</span>';
                    }

                    $('#btn_sync_history').on('click', function() {

                        $('#modal_sync_history').show();
                        $('#sync_history_loading').show();
                        $('#sync_history_content').hide();
                        $('#sync_history_empty').hide();
                        $('#sync_history_error').hide();
                        $('#sync_history_tbody').empty();

                        $.ajax({
                            url: fn_url('is2or_channel_integrations.sync_history&is_ajax=1'),
                            type: 'POST',
                            dataType: 'json',
                            data: { security_hash: _.security_hash },

                            success: function(response) {

                                var data = response;
                                if (response && typeof response.text === 'string') {
                                    try { data = JSON.parse(response.text); } catch(e) {}
                                }

                                $('#sync_history_loading').hide();

                                if (!data || data.error) {
                                    $('#sync_history_error')
                                        .text(data.message || 'Failed to load sync history.')
                                        .show();
                                    return;
                                }

                                if (!data.data || data.data.length === 0) {
                                    $('#sync_history_empty').show();
                                    return;
                                }

                                var rows = '';
                                $.each(data.data, function(i, row) {
                                    var completedAtDisplay = (row.completed_at && row.completed_at != 0) 
                                        ? formatTimestamp(row.completed_at) 
                                        : '-';
                                    var categoryBtn = (row.status === 'succeeded') 
                                        ? '<button type="button" class="btn btn-mini btn-primary btn-generate-category" data-job-id="' + row.job_id + '">Generate Category</button>' 
                                        : '-';

                                    rows += '<tr>'
                                        + '<td>' + (i + 1) + '</td>'
                                        + '<td>' + row.job_id + '</td>'
                                        + '<td>' + statusBadge(row.status) + '</td>'
                                        + '<td>' + (row.attempt_count || 0) + '</td>'
                                        + '<td>' + (row.success_count || 0) + '</td>'
                                        + '<td>' + (row.failure_count || 0) + '</td>'
                                        + '<td>' + completedAtDisplay + '</td>'
                                        + '<td>' + formatTimestamp(row.created_at) + '</td>'
                                        + '<td>' + categoryBtn + '</td>'
                                        + '</tr>';
                                });
                                $('#sync_history_tbody').html(rows);
                                $('#sync_history_content').show();
                            },

                            error: function() {
                                $('#sync_history_loading').hide();
                                $('#sync_history_error').text('Request failed. Please try again.').show();
                            }
                        });
                    });

                    $('#btn_close_history').on('click', function() {
                        $('#modal_sync_history').hide();
                    });

                    $('#modal_sync_history').on('click', function(e) {
                        if ($(e.target).is('#modal_sync_history')) {
                            $('#modal_sync_history').hide();
                        }
                    });

                });

            })(Tygh, Tygh.$);
            </script>
        </div>

        {* Zid Integration Card *}
        <div class="span6"
             style="border:1px solid #e5e5e5; padding:20px; border-radius:8px; background:#fff;">

            <div class="row-fluid"
                 style="display:flex; align-items:center; justify-content:space-between;">

                <div>
                    <h3 style="margin:0 0 5px 0;">
                        {__("is2or_channel_integrations.zid_channel")}
                    </h3>

                    <p style="color:#666; margin:0;">
                        {__("is2or_channel_integrations.zid_channel_description")}
                    </p>
                </div>

                <div>
                    {if $zid_status === 'active'}
                        <span class="label label-success"
                            style="padding:6px 12px; font-size:12px;">
                            {__("is2or_channel_integrations.connected")}
                        </span>

                        <div class="control-group">
                            <label class="control-label">
                                {__("is2or_channel_integrations.initial_sync")}:
                            </label>

                            <div class="controls">

                                <button type="button"
                                        class="btn btn-info"
                                        id="btn_import_zid">

                                    <i class="icon-download-alt"
                                       style="vertical-align:middle; margin-right:6px;"></i>

                                    <span style="vertical-align:middle;">
                                        {__("is2or_channel_integrations.import_products_from_zid")}
                                    </span>
                                </button>

                                <p class="muted description">
                                    {__("is2or_channel_integrations.import_products_description_zid")}
                                </p>

                                <script type="text/javascript">
                                (function(_, $) {
                                    $(document).ready(function() {
                                        $('#btn_import_zid').on('click', function() {
                                            var btn = $(this);
                                            btn.prop('disabled', true)
                                               .find('span').text('{__("is2or_channel_integrations.loading")}');
                                            $.ajax({
                                                url: fn_url('is2or_channel_integrations.trigger_import_zid&is_ajax=1'),
                                                type: 'POST',
                                                dataType: 'json',
                                                data: {
                                                    platform: 'zid',
                                                    security_hash: _.security_hash
                                                },
                                                success: function(response) {
                                                    var data = response;
                                                    if (response && typeof response.text === 'string') {
                                                        try { data = JSON.parse(response.text); } catch(e) {}
                                                    }
                                                    btn.prop('disabled', false)
                                                       .find('span').text('{__("is2or_channel_integrations.import_products_from_zid")}');
                                                    if (data && data.error === false) {
                                                        $.ceNotification('show', {
                                                            type: 'N',
                                                            title: '{__("notice")}',
                                                            message: 'Data synchronization has been triggered successfully. Products will be imported gradually in the background.'
                                                        });
                                                    } else if (data && data.error) {
                                                        $.ceNotification('show', {
                                                            type: 'E',
                                                            title: '{__("error")}',
                                                            message: data.message || '{__("is2or_channel_integrations.internal_server_error")}'
                                                        });
                                                    } else {
                                                        $.ceNotification('show', {
                                                            type: 'W',
                                                            title: '{__("warning")}',
                                                            message: 'Unexpected response from server.'
                                                        });
                                                    }
                                                },
                                                error: function(xhr, status, error) {
                                                    btn.prop('disabled', false)
                                                       .find('span').text('{__("is2or_channel_integrations.import_products_from_zid")}');
                                                    $.ceNotification('show', {
                                                        type: 'E',
                                                        title: '{__("error")}',
                                                        message: '{__("is2or_channel_integrations.internal_server_error")}'
                                                    });
                                                    console.error(xhr.responseText);
                                                }
                                            });
                                        });

                                        $('#sync_history_tbody_zid').on('click', '.btn-generate-category-zid', function() {
                                            var btn = $(this);
                                            btn.prop('disabled', true).text('Generating...');

                                            $.ajax({
                                                url: fn_url('is2or_channel_integrations.generate_category_zid&is_ajax=1'),
                                                type: 'POST',
                                                dataType: 'json',
                                                timeout: 360000,
                                                data: {
                                                    security_hash: _.security_hash
                                                },
                                                success: function(response) {
                                                    var data = response;
                                                    if (response && typeof response.text === 'string') {
                                                        try { data = JSON.parse(response.text); } catch(e) {}
                                                    }

                                                    // Tampilkan debug log di console, apapun hasilnya (sukses/error)
                                                    if (data && data.debug_log) {
                                                        console.group('is2or generate_category debug log');
                                                        data.debug_log.forEach(function(line) {
                                                            console.log(line);
                                                        });
                                                        console.groupEnd();
                                                    }

                                                    if (data && data.error === false) {
                                                        btn.prop('disabled', false).text('Generate Category').removeClass('btn-primary').addClass('btn-success');
                                                        $.ceNotification('show', {
                                                            type: 'N',
                                                            title: '{__("notice")}',
                                                            message: data.message || 'Generate AI categories completed.'
                                                        });
                                                    } else {
                                                        btn.prop('disabled', false).text('Generate Category');
                                                        $.ceNotification('show', {
                                                            type: 'E',
                                                            title: '{__("error")}',
                                                            message: (data && data.message) || 'Generate process failed.'
                                                        });
                                                    }
                                                },
                                                error: function(xhr, status, error) {
                                                    btn.prop('disabled', false).text('Generate Category');
                                                    $.ceNotification('show', { type: 'E', title: '{__("error")}', message: '{__("is2or_channel_integrations.internal_server_error")}' });

                                                    console.error("AJAX/Network Error: ", status, error);
                                                    console.error("Response Text: ", xhr.responseText);

                                                    // Coba parse responseText siapa tahu ada debug_log meski status HTTP error
                                                    if (xhr.responseText) {
                                                        try {
                                                            var errData = JSON.parse(xhr.responseText);
                                                            if (errData.debug_log) {
                                                                console.group('is2or generate_category debug log (from error response)');
                                                                errData.debug_log.forEach(function(line) {
                                                                    console.log(line);
                                                                });
                                                                console.groupEnd();
                                                            }
                                                        } catch(e) {
                                                            // responseText bukan JSON valid, tidak ada yang bisa di-parse
                                                        }
                                                    }
                                                }
                                            });
                                        });
                                    });
                                })(Tygh, Tygh.$);
                                </script>

                            </div>
                        </div>
                    {else}
                        <span class="label label-important" style="padding: 6px 12px; font-size: 12px;">{__("is2or_channel_integrations.disconnected")}</span>
                    {/if}
                </div>
            </div>

            <hr style="margin:15px 0;">

            <div class="buttons-container">

                {if $zid_status !== 'active'}

                    <button type="button"
                            class="btn btn-primary"
                            id="btn_connect_zid">
                        {__("is2or_channel_integrations.connect_zid")}
                    </button>

                    <script type="text/javascript">
                    (function(_, $) {
                        $(document).ready(function() {
                            $('#btn_connect_zid').on('click', function() {
                                var btn = $(this);
                                btn.prop('disabled', true)
                                   .text('{__("is2or_channel_integrations.loading")}');
                                $.ajax({
                                    url: fn_url('is2or_channel_integrations.request_authorize_url_zid&is_ajax=1'),
                                    type: 'POST',
                                    dataType: 'json',
                                    data: { security_hash: _.security_hash },
                                    success: function(response) {
                                        btn.prop('disabled', false)
                                           .text('{__("is2or_channel_integrations.connect_zid")}');
                                        var data = response;
                                        if (response && typeof response.text === 'string') {
                                            try { data = JSON.parse(response.text); } catch(e) {}
                                        }
                                        if (data && data.authorization_url) {
                                            window.location.href = data.authorization_url;
                                        } else {
                                            alert(data.message || '{__("is2or_channel_integrations.failed_get_authorize_url")}');
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        btn.prop('disabled', false)
                                           .text('{__("is2or_channel_integrations.connect_zid")}');
                                        alert('{__("is2or_channel_integrations.internal_server_error")}');
                                        console.error(xhr.responseText);
                                    }
                                });
                            });
                        });
                    })(Tygh, Tygh.$);
                    </script>

                {else}

                    <button type="button" class="btn btn-secondary" id="btn_sync_history_zid">
                        <i class="icon-time" style="margin-right:5px;"></i>
                        {__("is2or_channel_integrations.sync_history")}
                    </button>

                    <a href="{"is2or_channel_integrations.disconnect_zid?company_id=`$company_id`"|fn_url}"
                       class="btn btn-danger cm-confirm">
                        {__("is2or_channel_integrations.disconnect")}
                    </a>

                {/if}

            </div>

            {* Sync History Modal Zid *}
            <div id="modal_sync_history_zid"
                style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
                        background:rgba(0,0,0,0.5); z-index:9999; overflow-y:auto;">

                <div style="background:#fff; width:780px; margin:60px auto; border-radius:8px;
                            padding:24px; position:relative;">

                    <button type="button" id="btn_close_history_zid"
                            style="position:absolute; top:12px; right:16px; background:none;
                                   border:none; font-size:20px; cursor:pointer;">&#x2715;</button>

                    <h4 style="margin:0 0 16px 0;">
                        {__("is2or_channel_integrations.sync_history")} — Zid
                    </h4>

                    <div id="sync_history_loading_zid" style="text-align:center; padding:30px; color:#999;">
                        <i class="icon-spinner icon-spin"></i> Loading...
                    </div>

                    <div id="sync_history_empty_zid" style="display:none; text-align:center; padding:30px; color:#999;">
                        No sync history found.
                    </div>

                    <div id="sync_history_content_zid" style="display:none; max-height:480px; overflow-y:auto;">
                        <table class="table table-striped table-bordered" style="width:100%; font-size:13px;">
                            <thead>
                                <tr>
                                    <th style="width:40px;"></th>
                                    <th>Job ID</th>
                                    <th>Status</th>
                                    <th>Attempt</th>
                                    <th>Success</th>
                                    <th>Failed</th>
                                    <th>Completed At</th>
                                    <th>Sync Time</th>
                                    <th>Category</th>
                                </tr>
                            </thead>
                            <tbody id="sync_history_tbody_zid"></tbody>
                        </table>
                    </div>

                    <div id="sync_history_error_zid" style="display:none; color:#b94a48; padding:10px 0;"></div>
                </div>
            </div>

            <script type="text/javascript">
            (function(_, $) {
                $(document).ready(function() {

                    function formatTimestampZid(ts) {
                        if (!ts) return '—';
                        var d = new Date(parseInt(ts) * 1000);
                        return d.toLocaleString();
                    }

                    function statusBadgeZid(status) {
                        var color = {
                            'pending'   : '#f89406',
                            'succeeded' : '#468847',
                            'failed'    : '#b94a48',
                            'rejected'  : '#b94a48'
                        };
                        var bg = color[status] || '#999';
                        return '<span style="background:' + bg + '; color:#fff; padding:2px 8px;'
                             + ' border-radius:3px; font-size:11px;">' + status + '</span>';
                    }

                    $('#btn_sync_history_zid').on('click', function() {
                        $('#modal_sync_history_zid').show();
                        $('#sync_history_loading_zid').show();
                        $('#sync_history_content_zid').hide();
                        $('#sync_history_empty_zid').hide();
                        $('#sync_history_error_zid').hide();
                        $('#sync_history_tbody_zid').empty();

                        $.ajax({
                            url: fn_url('is2or_channel_integrations.sync_history_zid&is_ajax=1'),
                            type: 'POST',
                            dataType: 'json',
                            data: { security_hash: _.security_hash },
                            success: function(response) {
                                var data = response;
                                if (response && typeof response.text === 'string') {
                                    try { data = JSON.parse(response.text); } catch(e) {}
                                }
                                $('#sync_history_loading_zid').hide();
                                if (!data || data.error) {
                                    $('#sync_history_error_zid')
                                        .text(data.message || 'Failed to load sync history.')
                                        .show();
                                    return;
                                }
                                if (!data.data || data.data.length === 0) {
                                    $('#sync_history_empty_zid').show();
                                    return;
                                }
                                var rows = '';
                                $.each(data.data, function(i, row) {
                                    var completedAtDisplay = (row.completed_at && row.completed_at != 0)
                                        ? formatTimestampZid(row.completed_at)
                                        : '-';
                                    var categoryBtn = (row.status === 'succeeded') 
                                        ? '<button type="button" class="btn btn-mini btn-primary btn-generate-category-zid" data-job-id="' + row.job_id + '">Generate Category</button>' 
                                        : '-';

                                    rows += '<tr>'
                                        + '<td>' + (i + 1) + '</td>'
                                        + '<td>' + row.job_id + '</td>'
                                        + '<td>' + statusBadgeZid(row.status) + '</td>'
                                        + '<td>' + (row.attempt_count || 0) + '</td>'
                                        + '<td>' + (row.success_count || 0) + '</td>'
                                        + '<td>' + (row.failure_count || 0) + '</td>'
                                        + '<td>' + completedAtDisplay + '</td>'
                                        + '<td>' + formatTimestampZid(row.created_at) + '</td>'
                                        + '<td>' + categoryBtn + '</td>'
                                        + '</tr>';
                                });
                                $('#sync_history_tbody_zid').html(rows);
                                $('#sync_history_content_zid').show();
                            },
                            error: function() {
                                $('#sync_history_loading_zid').hide();
                                $('#sync_history_error_zid').text('Request failed. Please try again.').show();
                            }
                        });
                    });

                    $('#btn_close_history_zid').on('click', function() {
                        $('#modal_sync_history_zid').hide();
                    });

                    $('#modal_sync_history_zid').on('click', function(e) {
                        if ($(e.target).is('#modal_sync_history_zid')) {
                            $('#modal_sync_history_zid').hide();
                        }
                    });

                });
            })(Tygh, Tygh.$);
            </script>

        </div>
    </div>

    <div class="row-fluid" style="margin-top:20px;">

        {* Noon Integration Card *}
        <div class="span6"
             style="border:1px solid #e5e5e5; padding:20px; border-radius:8px; background:#fff;">

            <div class="row-fluid"
                 style="display:flex; align-items:center; justify-content:space-between;">

                <div>
                    <h3 style="margin:0 0 5px 0;">
                        {__("is2or_channel_integrations.noon_channel")}
                    </h3>

                    <p style="color:#666; margin:0;">
                        {__("is2or_channel_integrations.noon_channel_description")}
                    </p>
                </div>

                <div>
                    {if $noon_status === 'active'}
                        <span class="label label-success"
                            style="padding:6px 12px; font-size:12px;">
                            {__("is2or_channel_integrations.connected")}
                        </span>

                        <div class="control-group">
                            <label class="control-label">
                                {__("is2or_channel_integrations.initial_sync")}:
                            </label>

                            <div class="controls">

                                <button type="button"
                                        class="btn btn-info"
                                        id="btn_import_noon">

                                    <i class="icon-download-alt"
                                       style="vertical-align:middle; margin-right:6px;"></i>

                                    <span style="vertical-align:middle;">
                                        {__("is2or_channel_integrations.import_products_from_noon")}
                                    </span>
                                </button>

                                <p class="muted description">
                                    {__("is2or_channel_integrations.import_products_description_noon")}
                                </p>

                                <script type="text/javascript">
                                (function(_, $) {
                                    $(document).ready(function() {
                                        $('#btn_import_noon').on('click', function() {
                                            var btn = $(this);
                                            btn.prop('disabled', true)
                                               .find('span').text('{__("is2or_channel_integrations.loading")}');
                                            $.ajax({
                                                url: fn_url('is2or_channel_integrations.trigger_import_noon&is_ajax=1'),
                                                type: 'POST',
                                                dataType: 'json',
                                                data: {
                                                    platform: 'noon',
                                                    security_hash: _.security_hash
                                                },
                                                success: function(response) {
                                                    var data = response;
                                                    if (response && typeof response.text === 'string') {
                                                        try { data = JSON.parse(response.text); } catch(e) {}
                                                    }
                                                    btn.prop('disabled', false)
                                                       .find('span').text('{__("is2or_channel_integrations.import_products_from_noon")}');
                                                    if (data && data.error === false) {
                                                        $.ceNotification('show', {
                                                            type: 'N',
                                                            title: '{__("notice")}',
                                                            message: 'Data synchronization has been triggered successfully. Products will be imported gradually in the background.'
                                                        });
                                                    } else if (data && data.error) {
                                                        $.ceNotification('show', {
                                                            type: 'E',
                                                            title: '{__("error")}',
                                                            message: data.message || '{__("is2or_channel_integrations.internal_server_error")}'
                                                        });
                                                    } else {
                                                        $.ceNotification('show', {
                                                            type: 'W',
                                                            title: '{__("warning")}',
                                                            message: 'Unexpected response from server.'
                                                        });
                                                    }
                                                },
                                                error: function(xhr, status, error) {
                                                    btn.prop('disabled', false)
                                                       .find('span').text('{__("is2or_channel_integrations.import_products_from_noon")}');
                                                    $.ceNotification('show', {
                                                        type: 'E',
                                                        title: '{__("error")}',
                                                        message: '{__("is2or_channel_integrations.internal_server_error")}'
                                                    });
                                                    console.error(xhr.responseText);
                                                }
                                            });
                                        });

                                        $('#sync_history_tbody_noon').on('click', '.btn-generate-category-noon', function() {
                                            var btn = $(this);
                                            btn.prop('disabled', true).text('Generating...');

                                            $.ajax({
                                                url: fn_url('is2or_channel_integrations.generate_category_noon&is_ajax=1'),
                                                type: 'POST',
                                                dataType: 'json',
                                                timeout: 360000,
                                                data: {
                                                    security_hash: _.security_hash
                                                },
                                                success: function(response) {
                                                    var data = response;
                                                    if (response && typeof response.text === 'string') {
                                                        try { data = JSON.parse(response.text); } catch(e) {}
                                                    }

                                                    // Tampilkan debug log di console, apapun hasilnya (sukses/error)
                                                    if (data && data.debug_log) {
                                                        console.group('is2or generate_category debug log');
                                                        data.debug_log.forEach(function(line) {
                                                            console.log(line);
                                                        });
                                                        console.groupEnd();
                                                    }

                                                    if (data && data.error === false) {
                                                        btn.prop('disabled', false).text('Generate Category').removeClass('btn-primary').addClass('btn-success');
                                                        $.ceNotification('show', {
                                                            type: 'N',
                                                            title: '{__("notice")}',
                                                            message: data.message || 'Generate AI categories completed.'
                                                        });
                                                    } else {
                                                        btn.prop('disabled', false).text('Generate Category');
                                                        $.ceNotification('show', {
                                                            type: 'E',
                                                            title: '{__("error")}',
                                                            message: (data && data.message) || 'Generate process failed.'
                                                        });
                                                    }
                                                },
                                                error: function(xhr, status, error) {
                                                    btn.prop('disabled', false).text('Generate Category');
                                                    $.ceNotification('show', { type: 'E', title: '{__("error")}', message: '{__("is2or_channel_integrations.internal_server_error")}' });

                                                    console.error("AJAX/Network Error: ", status, error);
                                                    console.error("Response Text: ", xhr.responseText);

                                                    // Coba parse responseText siapa tahu ada debug_log meski status HTTP error
                                                    if (xhr.responseText) {
                                                        try {
                                                            var errData = JSON.parse(xhr.responseText);
                                                            if (errData.debug_log) {
                                                                console.group('is2or generate_category debug log (from error response)');
                                                                errData.debug_log.forEach(function(line) {
                                                                    console.log(line);
                                                                });
                                                                console.groupEnd();
                                                            }
                                                        } catch(e) {
                                                            // responseText bukan JSON valid, tidak ada yang bisa di-parse
                                                        }
                                                    }
                                                }
                                            });
                                        });
                                    });
                                })(Tygh, Tygh.$);
                                </script>

                            </div>
                        </div>
                    {else}
                        <span class="label label-important" style="padding: 6px 12px; font-size: 12px;">{__("is2or_channel_integrations.disconnected")}</span>
                    {/if}
                </div>
            </div>

            <hr style="margin:15px 0;">

            <div class="buttons-container">

                {if $noon_status !== 'active'}

                    <button type="button"
                            class="btn btn-primary"
                            id="btn_connect_noon">
                        {__("is2or_channel_integrations.connect_noon")}
                    </button>

                    <script type="text/javascript">
                    (function(_, $) {
                        $(document).ready(function() {
                            $('#btn_connect_noon').on('click', function() {
                                var btn = $(this);
                                btn.prop('disabled', true)
                                   .text('{__("is2or_channel_integrations.loading")}');
                                $.ajax({
                                    url: fn_url('is2or_channel_integrations.request_authorize_url_noon&is_ajax=1'),
                                    type: 'POST',
                                    dataType: 'json',
                                    data: { security_hash: _.security_hash },
                                    success: function(response) {
                                        btn.prop('disabled', false)
                                           .text('{__("is2or_channel_integrations.connect_noon")}');
                                        var data = response;
                                        if (response && typeof response.text === 'string') {
                                            try { data = JSON.parse(response.text); } catch(e) {}
                                        }
                                        if (data && data.authorization_url) {
                                            window.location.href = data.authorization_url;
                                        } else {
                                            alert(data.message || '{__("is2or_channel_integrations.failed_get_authorize_url")}');
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        btn.prop('disabled', false)
                                           .text('{__("is2or_channel_integrations.connect_noon")}');
                                        alert('{__("is2or_channel_integrations.internal_server_error")}');
                                        console.error(xhr.responseText);
                                    }
                                });
                            });
                        });
                    })(Tygh, Tygh.$);
                    </script>

                {else}

                    <button type="button" class="btn btn-secondary" id="btn_sync_history_noon">
                        <i class="icon-time" style="margin-right:5px;"></i>
                        {__("is2or_channel_integrations.sync_history")}
                    </button>

                    <a href="{"is2or_channel_integrations.disconnect_noon?company_id=`$company_id`"|fn_url}"
                       class="btn btn-danger cm-confirm">
                        {__("is2or_channel_integrations.disconnect")}
                    </a>

                {/if}

            </div>

            {* Sync History Modal Noon *}
            <div id="modal_sync_history_noon"
                style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
                        background:rgba(0,0,0,0.5); z-index:9999; overflow-y:auto;">

                <div style="background:#fff; width:780px; margin:60px auto; border-radius:8px;
                            padding:24px; position:relative;">

                    <button type="button" id="btn_close_history_noon"
                            style="position:absolute; top:12px; right:16px; background:none;
                                   border:none; font-size:20px; cursor:pointer;">&#x2715;</button>

                    <h4 style="margin:0 0 16px 0;">
                        {__("is2or_channel_integrations.sync_history")} — Noon
                    </h4>

                    <div id="sync_history_loading_noon" style="text-align:center; padding:30px; color:#999;">
                        <i class="icon-spinner icon-spin"></i> Loading...
                    </div>

                    <div id="sync_history_empty_noon" style="display:none; text-align:center; padding:30px; color:#999;">
                        No sync history found.
                    </div>

                    <div id="sync_history_content_noon" style="display:none; max-height:480px; overflow-y:auto;">
                        <table class="table table-striped table-bordered" style="width:100%; font-size:13px;">
                            <thead>
                                <tr>
                                    <th style="width:40px;"></th>
                                    <th>Job ID</th>
                                    <th>Status</th>
                                    <th>Attempt</th>
                                    <th>Success</th>
                                    <th>Failed</th>
                                    <th>Completed At</th>
                                    <th>Sync Time</th>
                                    <th>Category</th>
                                </tr>
                            </thead>
                            <tbody id="sync_history_tbody_noon"></tbody>
                        </table>
                    </div>

                    <div id="sync_history_error_noon" style="display:none; color:#b94a48; padding:10px 0;"></div>
                </div>
            </div>

            <script type="text/javascript">
            (function(_, $) {
                $(document).ready(function() {

                    function formatTimestampNoon(ts) {
                        if (!ts) return '—';
                        var d = new Date(parseInt(ts) * 1000);
                        return d.toLocaleString();
                    }

                    function statusBadgeNoon(status) {
                        var color = {
                            'pending'   : '#f89406',
                            'succeeded' : '#468847',
                            'failed'    : '#b94a48',
                            'rejected'  : '#b94a48'
                        };
                        var bg = color[status] || '#999';
                        return '<span style="background:' + bg + '; color:#fff; padding:2px 8px;'
                             + ' border-radius:3px; font-size:11px;">' + status + '</span>';
                    }

                    $('#btn_sync_history_noon').on('click', function() {
                        $('#modal_sync_history_noon').show();
                        $('#sync_history_loading_noon').show();
                        $('#sync_history_content_noon').hide();
                        $('#sync_history_empty_noon').hide();
                        $('#sync_history_error_noon').hide();
                        $('#sync_history_tbody_noon').empty();

                        $.ajax({
                            url: fn_url('is2or_channel_integrations.sync_history_noon&is_ajax=1'),
                            type: 'POST',
                            dataType: 'json',
                            data: { security_hash: _.security_hash },
                            success: function(response) {
                                var data = response;
                                if (response && typeof response.text === 'string') {
                                    try { data = JSON.parse(response.text); } catch(e) {}
                                }
                                $('#sync_history_loading_noon').hide();
                                if (!data || data.error) {
                                    $('#sync_history_error_noon')
                                        .text(data.message || 'Failed to load sync history.')
                                        .show();
                                    return;
                                }
                                if (!data.data || data.data.length === 0) {
                                    $('#sync_history_empty_noon').show();
                                    return;
                                }
                                var rows = '';
                                $.each(data.data, function(i, row) {
                                    var completedAtDisplay = (row.completed_at && row.completed_at != 0)
                                        ? formatTimestampNoon(row.completed_at)
                                        : '-';
                                    var categoryBtn = (row.status === 'succeeded') 
                                        ? '<button type="button" class="btn btn-mini btn-primary btn-generate-category-noon" data-job-id="' + row.job_id + '">Generate Category</button>' 
                                        : '-';

                                    rows += '<tr>'
                                        + '<td>' + (i + 1) + '</td>'
                                        + '<td>' + row.job_id + '</td>'
                                        + '<td>' + statusBadgeNoon(row.status) + '</td>'
                                        + '<td>' + (row.attempt_count || 0) + '</td>'
                                        + '<td>' + (row.success_count || 0) + '</td>'
                                        + '<td>' + (row.failure_count || 0) + '</td>'
                                        + '<td>' + completedAtDisplay + '</td>'
                                        + '<td>' + formatTimestampNoon(row.created_at) + '</td>'
                                        + '<td>' + categoryBtn + '</td>'
                                        + '</tr>';
                                });
                                $('#sync_history_tbody_noon').html(rows);
                                $('#sync_history_content_noon').show();
                            },
                            error: function() {
                                $('#sync_history_loading_noon').hide();
                                $('#sync_history_error_noon').text('Request failed. Please try again.').show();
                            }
                        });
                    });

                    $('#btn_close_history_noon').on('click', function() {
                        $('#modal_sync_history_noon').hide();
                    });

                    $('#modal_sync_history_noon').on('click', function(e) {
                        if ($(e.target).is('#modal_sync_history_noon')) {
                            $('#modal_sync_history_noon').hide();
                        }
                    });

                });
            })(Tygh, Tygh.$);
            </script>

        </div>
    </div>

{/capture}

{include
    file="common/mainbox.tpl"
    title=__("is2or_channel_integrations.channel_integrations")
    content=$smarty.capture.mainbox
}