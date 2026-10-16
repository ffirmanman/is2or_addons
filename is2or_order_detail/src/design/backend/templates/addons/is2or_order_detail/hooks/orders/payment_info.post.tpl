<div class="control-group">
    <div class="control-label">{__("refund_status")}</div>
    <div id="tygh_refund_process_status" class="controls">
        {$refund_details.status_name}
    </div>
</div>

<div class="control-group">
    <div class="control-label">{__("refund_action")}</div>
    <div id="tygh_refund_process_action" class="controls">
        {$refund_details.property}
    </div>
</div>

<div class="control-group">
    <div class="control-label">{__("refund_total")}</div>
    <div id="tygh_refund_process_total" class="controls">
        {$refund_details.total_amount}
    </div>
</div>

<div class="control-group">
    <div class="control-label">{__("refund_description")}</div>
    <div id="tygh_refund_process_comment" class="controls">
        {$refund_details.comment}
    </div>
</div>

<div class="control-group">
    <div class="control-label">{__("refund_url")}</div>
    <div id="tygh_refund_process_details" class="controls">
        {if $refund_details.refund_url}
            <a href="{$refund_details.refund_url}" target="_blank" class="strong">
                <i class="icon-external-link"></i> {__("view_refund_details")}
            </a>
        {else}
            <span class="text-muted">-</span>
        {/if}
    </div>
</div>